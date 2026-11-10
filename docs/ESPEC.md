# Agendaí: especificação

SaaS multi-tenant de agendamento para pequenos negócios de serviço (salões, barbearias,
clínicas, estúdios). Cada negócio tem a sua página pública de agendamento, a sua equipe e
os seus serviços, e recebe o pagamento antecipado do cliente.

O projeto de portfólio precisa provar estas habilidades, que são as que as vagas pedem:
sistema de negócio real, multi-tenant, pagamentos com webhook, filas, testes, CI, Docker,
API documentada, React e deploy.

---

## 1. Pessoas e contas de demonstração

| Papel | O que faz | Login demo |
|---|---|---|
| **Dono(a)** (`owner`) | configura o negócio, serviços, equipe, vê o dashboard e a auditoria | `dono@demo.agendai.dev` |
| **Profissional** (`staff`) | vê e gerencia a própria agenda, marca falta/concluído | `profissional@demo.agendai.dev` |
| **Cliente final** | agenda pela página pública, paga, recebe lembrete, cancela pelo link | sem conta (link mágico no e-mail) |

A demo tem um botão **"Entrar como dono(a)"** na tela de login. O recrutador precisa ver o
sistema em menos de 1 minuto. Os dados demo são recriados todo dia por um job agendado.

## 2. Stack

| Camada | Escolha | Por quê |
|---|---|---|
| Backend | Laravel 13, PHP 8.4 | o mercado PHP pede Laravel |
| Frontend | Inertia + React + TypeScript + Tailwind | React aparece em 52% das vagas analisadas |
| Banco | PostgreSQL 17 | permite a *exclusion constraint* que impede dois agendamentos no mesmo horário |
| Fila / cache | Redis + Laravel Horizon | lembretes, expiração de reservas, webhooks |
| Testes | Pest, Larastan nível 6, Pint | qualidade visível no CI |
| Ambiente | Docker Compose (app, postgres, redis, mailpit) | `docker compose up` e pronto |
| CI | GitHub Actions: lint, análise estática, testes, build do front | badge verde no README |
| API | Sanctum (tokens) + Scribe (documentação OpenAPI) | |
| Pagamento | Mercado Pago Checkout Pro, em sandbox | o gateway mais comum no Brasil |
| Deploy | decidido no M6 (Laravel Cloud ou Railway) | precisa rodar o worker da fila |

Código, commits e README em **inglês** (vale também para vagas internacionais). A interface
fica em PT-BR, com i18n preparado para EN.

## 3. Decisões de arquitetura (viram o estudo de caso)

1. **Multi-tenancy com banco único e `tenant_id`.** A trait `BelongsToTenant` aplica um global
   scope em toda consulta e preenche o `tenant_id` ao criar. Um teste de arquitetura (Pest
   `arch()`) falha se algum model de negócio não usar a trait. Testes de feature provam que o
   tenant A recebe 404 ao acessar recursos do tenant B.
2. **Página pública por caminho (`/b/{slug}`), não por subdomínio.** Simplifica o deploy e o
   HTTPS. A decisão fica registrada com o trade-off.
3. **Sem agendamento duplicado, garantido pelo banco.** Coluna `tstzrange` + `EXCLUDE USING gist
   (professional_id WITH =, period WITH &&) WHERE status IN ('pending_payment','confirmed')`.
   Duas pessoas clicando no mesmo horário ao mesmo tempo: uma recebe a vaga e a outra recebe
   "horário acabou de ser reservado". Há um teste de concorrência para isso.
4. **Reserva com prazo.** O agendamento nasce `pending_payment` e segura o horário por 15
   minutos. Um job agendado expira os não pagos e libera o horário.
5. **Webhook idempotente.** `webhook_events.provider_event_id` é único. A assinatura
   (`x-signature`) é validada antes de qualquer processamento. O status do pagamento é
   consultado na API do Mercado Pago, sem confiar no corpo do webhook. Processar o mesmo evento
   duas vezes não muda nada (há teste para isso).
6. **Fuso horário por negócio.** Tudo é gravado em UTC e exibido no fuso do tenant. Há testes
   na virada do horário de verão de outros países (o Brasil não tem mais, mas a API aceita
   negócios de fora).
7. **Auditoria.** Ações sensíveis (preço alterado, agendamento cancelado pelo negócio, papel
   de usuário alterado) gravam `audit_logs` com antes e depois.

## 4. Modelo de dados

```
tenants           id, name, slug (único), timezone, currency, booking_window_days,
                  cancellation_hours, mp_access_token (criptografado), created_at
users             id, tenant_id, name, email, password, role (owner|staff), is_bookable
services          id, tenant_id, name, description, duration_min, buffer_min, price_cents, active
service_user      service_id, user_id                       -- quem executa cada serviço
availability_rules id, tenant_id, user_id, weekday (0-6), start_time, end_time
time_off          id, tenant_id, user_id, starts_at, ends_at, reason
customers         id, tenant_id, name, email, phone          -- único (tenant_id, email)
appointments      id, tenant_id, service_id, professional_id, customer_id,
                  period tstzrange, status, price_cents, expires_at,
                  cancel_token (hash), cancelled_at, cancelled_by, notes
                  EXCLUDE gist (professional_id, period) para status ativos
payments          id, tenant_id, appointment_id, provider, provider_payment_id,
                  status, amount_cents, paid_at
webhook_events    id, provider, provider_event_id (único), type, payload jsonb,
                  received_at, processed_at, error
audit_logs        id, tenant_id, user_id, action, subject_type, subject_id,
                  changes jsonb, ip, created_at
```

Status do agendamento: `pending_payment → confirmed → completed | no_show`, e
`pending_payment → expired`, além de `confirmed → cancelled`.

## 5. Telas

**Público**
- `/b/{slug}`: escolher serviço → profissional (ou "qualquer um") → dia → horário → dados → pagar
- `/b/{slug}/agendamento/{token}`: ver, adicionar ao calendário (.ics) e cancelar dentro do prazo

**Painel (dono/profissional)**
- Agenda dia/semana por profissional, com arrastar para remarcar (dono)
- Agendamentos: lista com filtros e ações (concluído, falta, cancelar)
- Serviços, Equipe (convite por e-mail), Horários e folgas
- Clientes: histórico e taxa de falta
- Dashboard: receita do mês, ocupação por profissional, taxa de falta e serviços mais vendidos
- Configurações: dados do negócio, fuso, política de cancelamento, conexão Mercado Pago
- Auditoria (só dono)

**Landing** `/`: o que é, prints, "Ver demo", link para o repositório

## 6. API pública `/api/v1` (Sanctum, documentada com Scribe)

```
GET  /services                       serviços ativos do tenant do token
GET  /availability?service=&date=    horários livres
POST /appointments                   cria a reserva (Idempotency-Key obrigatório)
GET  /appointments/{id}
POST /appointments/{id}/cancel
```
Rate limit por token. Erros no formato `application/problem+json`.

## 7. Marcos (cerca de 1 por semana, meio período)

Cada marco termina com o CI verde, deploy de preview e uma seção atualizada no README.

| # | Entrega | Pronto quando |
|---|---|---|
| **M0** | Laravel 13 + Inertia/React/TS + Docker Compose + Pest/Larastan/Pint + GitHub Actions | `docker compose up` sobe tudo e o CI fica verde |
| **M1** | Tenants, cadastro do negócio, login, papéis, `BelongsToTenant`, auditoria base | testes de isolamento entre tenants e teste de arquitetura passando |
| **M2** | Serviços, equipe, horários e folgas (CRUDs com validação e policies) | o dono configura o negócio de ponta a ponta |
| **M3** | Motor de horários livres + página pública + reserva com exclusion constraint | teste de concorrência prova que não há reserva duplicada |
| **M4** | Mercado Pago sandbox, webhook assinado e idempotente, expiração de reservas | teste com webhook duplicado e fora de ordem |
| **M5** | E-mails de confirmação/lembrete na fila (Horizon), cancelamento por link, dashboard | lembrete 24 h antes chega no Mailpit |
| **M6** | API v1 + Scribe, seed demo, deploy, landing, README com prints/GIF e estudo de caso | link no portfólio, com login demo funcionando |

## 8. Fora do escopo (de propósito)

App mobile, WhatsApp real (fica uma interface `ReminderChannel` com driver de log e
um texto explicando como plugar), planos/assinatura do SaaS e multi-idioma completo.
Isso aparece no README como "próximos passos", o que também mostra capacidade de priorizar.

## 9. Estudo de caso (texto para o portfólio)

1. Problema: pequenos negócios perdem receita com falta de cliente e marcação no WhatsApp.
2. Decisão difícil 1: como garantir zero reserva duplicada (constraint no banco × lock na aplicação).
3. Decisão difícil 2: webhook que chega duas vezes ou fora de ordem.
4. Isolamento entre clientes e como os testes provam isso.
5. Números: cobertura de testes, tempo de resposta do cálculo de horários, tempo do CI.
