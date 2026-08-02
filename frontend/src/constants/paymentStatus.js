import { semantic } from '@/design/tokens'

/**
 * Les statuts de paiement — source unique.
 *
 * Domaine distinct de celui des réservations : un paiement et un rendez-vous
 * ne partagent ni leur vocabulaire ni leur cycle de vie. Les mélanger dans une
 * seule table aurait produit la même confusion que celle qu'on vient de
 * défaire, dans l'autre sens.
 *
 * Les valeurs correspondent aux constantes de App\Models\Payment.
 */

export const PaymentStatus = {
  PENDING: 'pending',
  SUCCESSFUL: 'successful',
  FAILED: 'failed',
  CANCELLED: 'cancelled',
}

export const PAYMENT_STATUSES = {
  [PaymentStatus.SUCCESSFUL]: {
    label: 'Payé',
    tone: semantic.confirmed,
  },
  [PaymentStatus.PENDING]: {
    // « En cours » plutôt que « En attente » : sur mobile money, la
    // confirmation arrive souvent après le retour au navigateur, et le
    // commerçant doit comprendre qu'il n'a rien à faire.
    label: 'En cours',
    tone: semantic.pending,
  },
  [PaymentStatus.FAILED]: {
    label: 'Échoué',
    tone: semantic.cancelled,
  },
  [PaymentStatus.CANCELLED]: {
    label: 'Annulé',
    tone: semantic.noShow,
  },
}

export function describePayment(status) {
  return (
    PAYMENT_STATUSES[status] ?? {
      label: String(status ?? '—'),
      tone: semantic.noShow,
    }
  )
}
