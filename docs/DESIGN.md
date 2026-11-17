# Agendaí: design notes

The brief every screen follows. Token values live in `resources/css/app.css`; this file says what
they mean. Written after a review against the claude-design-premium audit checklists.

## Surfaces

- **Product** (dashboard, settings, agenda): calm, dense enough to scan many times a day. Uses
  the shadcn components already in `resources/js/components/ui`. No decorative motion.
- **Public** (landing `/`, booking page `/b/{slug}`): the customer of a salon or clinic on a
  phone, often in a hurry. Mobile first, one decision per step, large touch targets.

## Color

- One brand color: teal `--primary`, used for primary actions, focus rings and the "paid"
  status. Everything else stays neutral.
- Status uses text color plus a word, never color alone ("Pago", "Aguardando pagamento").
- No gradients, glows or purple. They read as template defaults.

## Type and layout

- Instrument Sans from the starter kit, one weight jump between heading and body.
- Times use `font-mono tabular-nums` so columns of hours line up.
- Prefer divided lists to card grids. A card is for something you click or that stands alone.

## Copy

- The interface is in Portuguese (pt-BR); code, commits and README in English.
- Buttons name the action ("Cadastrar meu negócio", not "Começar").
- Claims about the system must be true today. Unshipped work is marked "Próximo".
- Plain punctuation: no em dashes, arrows or typographic ellipses.

## Accessibility baseline

- Touch targets at least 40px high (44px on the public booking flow).
- Every input has a visible label. Errors sit under their field.
- Respect `prefers-reduced-motion`.
- This is a design review, not a WCAG certification. Test with keyboard and a screen reader
  before calling a flow done.
