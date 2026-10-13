# MFA prototype enhancement plan

## Agreed scope

This project remains an academic prototype demonstrating verification usability and backup verification behavior. It is not intended for real institutional authentication.

## Plan status

The planning interview has established the scope below. Awaiting user confirmation before implementation.

Implementation begins after the interview establishes a shared understanding and the user confirms the plan.

## Backup eligibility

Backup verification is available when verification email delivery fails, the email does not arrive, or the user cannot access their inbox. A wrong number selection or an active security lockout must not automatically unlock backup verification or bypass account protection.

## Backup method

The backup method is single-use recovery codes saved by the user before primary verification becomes unavailable. Each successfully used code becomes invalid for future sign-ins.

## Recovery-code preparation

After successful email verification, users are offered a recovery-code setup step where they can save or print their codes for future sign-ins.

Each set contains five single-use recovery codes. The setup step asks users to confirm "I saved my codes" before continuing.

Setup is required after the first successful email verification for an account without a recovery-code set. Later sign-ins retain the remaining codes rather than automatically creating a new set. Replacing a set requires successful email verification and invalidates every code in the previous set.

A separate classroom demo setup provides prepared accounts and recovery codes so email-failure scenarios can be demonstrated immediately. Demo preparation must be clearly distinguished from the normal sign-in and recovery flow.

## Switching verification methods

Switching to recovery codes cancels the pending email verification and invalidates its approval link. Returning to email verification requires a fresh request. Each sign-in has one active verification method.

## Lost recovery codes

Users are offered a "Lost your recovery codes?" option. If email is accessible, successful email verification allows generation of replacement recovery codes, invalidating the old set. If both email and saved codes are unavailable, users can request helpdesk recovery.

Submitting a helpdesk recovery request displays a simulation alert stating that the recovery desk received the request. No actual request is sent, no reviewer screen or identity-check workflow is implemented, and the acknowledgement does not approve sign-in or issue replacement codes. The alert must clearly identify the interaction as a simulation.

## Interface scope

Keep the current visual design. Add only the interface elements needed for recovery-code setup, backup verification, lost-code options, and the simulated helpdesk acknowledgement. No general visual redesign or polish is requested.

## Account protection

Recovery-code verification follows the current account lockout behavior rather than introducing a separate three-error allowance. An invalid or already-used recovery code triggers the same immediate account lockout as a wrong email-number selection. Lockouts start at two minutes and increase by five minutes for repeated lockouts, capped at sixty minutes, using the existing escalation/reset rules. Active account lockouts block both verification methods; changing methods or restarting sign-in does not bypass them.

## Classroom demonstration

Provide a clearly labelled control, available only in demo mode, to simulate email sending failure and demonstrate backup verification with prepared demo accounts. A simulated delivery failure does not count as an incorrect verification attempt, matching current failed-send behavior.

## Implementation outline

1. Add account-associated recovery-code storage without overwriting codes when existing strike/lockout functions update account data. Store code hashes; prevent successful reuse, including concurrent submissions.
2. Add first-time code setup, save/print actions, acknowledgement, and email-verified replacement using the current visual design.
3. Add recovery-code entry from email delivery errors and the waiting screen. Enforce browser ownership and account protection, and invalidate the pending email request when switching. A failed email send must still allow an eligible user to begin a bound backup verification request.
4. Add lost-code choices and a clearly labelled simulated helpdesk receipt popup. The popup sends no external message and grants no access.
5. Add isolated demo preparation and a demo-only email-failure control. Keep demo credentials and controls separate from the normal flow.
6. Preserve the existing email verification and dashboard flow, adapting the success indicator to the method actually used.

## Verification scenarios

- Successful first email verification requires saving five recovery codes; later sign-ins preserve unused codes.
- Delivery failure, missing email, and inaccessible inbox each allow a prepared user to use backup verification.
- A correct recovery code approves its bound sign-in once; reuse and invalid codes follow the existing lockout.
- Active lockouts apply to both methods, including after restarting or switching.
- An email link cancelled by switching cannot approve the sign-in when it arrives late.
- Returning to email creates a fresh verification request.
- Email-verified replacement invalidates the entire previous code set.
- Lost-code helpdesk requests show only a simulation acknowledgement and do not approve sign-in.
- Demo failure controls are available only in demo mode; simulated delivery failure does not count as a wrong verification attempt.
- Existing email number matching, expiry, cancellation, and successful dashboard access continue to work.
