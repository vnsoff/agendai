import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';
/* @chisel-registration */
import { register } from '@/routes';
/* @end-chisel-registration */

const REPO_URL = 'https://github.com/vnsoff/agendai';

// Illustrative day view: shows what the product is about before any screenshot exists.
const SAMPLE_DAY = [
    { time: '09:00', service: 'Corte feminino', who: 'Marina', status: 'Pago' },
    { time: '10:00', service: 'Escova', who: 'Paula', status: 'Pago' },
    {
        time: '10:45',
        service: 'Coloração',
        who: 'Rita',
        status: 'Aguardando pagamento',
    },
    { time: '13:30', service: 'Corte masculino', who: 'João', status: 'Pago' },
];

// Mirrors the milestones in docs/ESPEC.md. Update when a milestone ships.
const DECISIONS = [
    {
        title: 'Dados de cada negócio isolados',
        body: 'Toda consulta passa por um global scope que falha fechado: sem negócio definido, a consulta gera erro em vez de devolver os dados de todos. Um teste de arquitetura quebra o CI se alguma tabela com tenant_id ficar de fora.',
        done: true,
    },
    {
        title: 'Auditoria com antes e depois',
        body: 'Mudanças no negócio ficam registradas com o valor anterior e o novo de cada campo, quem fez e de qual IP.',
        done: true,
    },
    {
        title: 'Nenhum horário reservado duas vezes',
        body: 'Uma exclusion constraint do PostgreSQL impede dois agendamentos sobrepostos para o mesmo profissional, mesmo com dois clientes clicando ao mesmo tempo.',
        done: false,
    },
    {
        title: 'Pagamento antecipado com webhook idempotente',
        body: 'A reserva segura o horário por 15 minutos. O webhook do Mercado Pago é validado pela assinatura, e processar o mesmo evento duas vezes não muda nada.',
        done: false,
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Agendamento online para pequenos negócios" />
            <div className="min-h-screen bg-background text-foreground">
                <header className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <span className="text-lg font-semibold tracking-tight">
                        Agendaí
                    </span>
                    <nav className="flex items-center gap-2 text-sm">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex h-10 items-center rounded-md bg-primary px-4 font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                Abrir painel
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-flex h-10 items-center rounded-md px-3 font-medium hover:bg-accent"
                                >
                                    Entrar
                                </Link>
                                {/* @chisel-registration */}
                                <Link
                                    href={register()}
                                    className="inline-flex h-10 items-center rounded-md bg-primary px-4 font-medium text-primary-foreground hover:bg-primary/90"
                                >
                                    Criar conta
                                </Link>
                                {/* @end-chisel-registration */}
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto max-w-5xl px-4 sm:px-6">
                    <section className="grid items-center gap-12 py-14 md:grid-cols-[1.1fr_0.9fr] md:py-20">
                        <div>
                            <h1 className="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                                O cliente marca e paga. Você só atende.
                            </h1>
                            <p className="mt-5 max-w-xl text-lg text-muted-foreground">
                                Agenda online para salões, barbearias e
                                clínicas: cada negócio tem a sua página de
                                agendamento, a sua equipe e os seus serviços, e
                                recebe o pagamento antes do horário.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                {/* @chisel-registration */}
                                {!auth.user && (
                                    <Link
                                        href={register()}
                                        className="inline-flex h-11 items-center rounded-md bg-primary px-5 font-medium text-primary-foreground hover:bg-primary/90"
                                    >
                                        Cadastrar meu negócio
                                    </Link>
                                )}
                                {/* @end-chisel-registration */}
                                <a
                                    href={REPO_URL}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex h-11 items-center rounded-md border px-5 font-medium hover:bg-accent"
                                >
                                    Ver o código no GitHub
                                </a>
                            </div>
                            <p className="mt-4 text-sm text-muted-foreground">
                                Projeto de portfólio em desenvolvimento. O
                                pagamento roda em modo sandbox.
                            </p>
                        </div>

                        <figure className="rounded-xl border bg-card p-5 shadow-sm">
                            <figcaption className="flex items-baseline justify-between text-sm">
                                <span className="font-medium">
                                    Studio Bella · terça, 18
                                </span>
                                <span className="text-muted-foreground">
                                    Exemplo
                                </span>
                            </figcaption>
                            <ul className="mt-4 divide-y">
                                {SAMPLE_DAY.map((slot) => (
                                    <li
                                        key={slot.time}
                                        className="grid grid-cols-[3.5rem_1fr] gap-3 py-3 text-sm"
                                    >
                                        <span className="font-mono text-muted-foreground tabular-nums">
                                            {slot.time}
                                        </span>
                                        <span className="min-w-0">
                                            <span className="block font-medium">
                                                {slot.service}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {slot.who} ·{' '}
                                                <span
                                                    className={
                                                        slot.status === 'Pago'
                                                            ? 'text-primary'
                                                            : undefined
                                                    }
                                                >
                                                    {slot.status}
                                                </span>
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </figure>
                    </section>

                    <section className="border-t py-14">
                        <h2 className="text-2xl font-semibold tracking-tight">
                            Como funciona por dentro
                        </h2>
                        <p className="mt-2 max-w-2xl text-muted-foreground">
                            As decisões de engenharia que fazem o sistema
                            aguentar uso real. O que está marcado como próximo
                            ainda não foi entregue.
                        </p>
                        <ul className="mt-8 grid gap-x-12 sm:grid-cols-2">
                            {DECISIONS.map((item) => (
                                <li key={item.title} className="border-t py-6">
                                    <span
                                        className={`text-xs font-medium ${item.done ? 'text-primary' : 'text-muted-foreground'}`}
                                    >
                                        {item.done ? 'Pronto' : 'Próximo'}
                                    </span>
                                    <h3 className="mt-1 font-medium">
                                        {item.title}
                                    </h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {item.body}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>
                </main>

                <footer className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 border-t px-4 py-8 text-sm text-muted-foreground sm:px-6">
                    <span>
                        Laravel, React e PostgreSQL. Feito por Vanessa
                        Christoff.
                    </span>
                    <a
                        href={REPO_URL}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="underline-offset-4 hover:underline"
                    >
                        github.com/vnsoff/agendai
                    </a>
                </footer>
            </div>
        </>
    );
}
