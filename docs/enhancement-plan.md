# MFA prototype enhancement plan

## Agreed scope

This remains an academic prototype. Keep the current visual design and add only the agreed verification and simulation flows. Implementation is on `codex/mfa-recovery-plan`.

## Verification methods

Email number matching remains the default. Users can also sign in with one permanent security code saved during first-time setup after successful email verification.

The security code is reusable: successful sign-ins never consume, expire, or rotate it. A sign-in request still expires after five minutes. Store only the code hash in account data; show plaintext temporarily during setup for saving or printing. Require "I saved my security code" before continuing to the dashboard.

## Next-login preference

After signing in using either method, users can select "Use security code in your next login". The saved account preference starts security code verification without sending an email. Email can be selected as the preferred method from the dashboard. The security-code page has no email-verification action; it offers code entry, helpdesk assistance, and cancellation. Accounts without a configured security code use email for initial setup.

## Backup and switching

Security code verification is also available when email delivery fails, the email never arrives, or the inbox is inaccessible. Switching from email cancels its pending approval link. Browser binding and account protection apply to both methods. Email requests can still be resent from the email-verification page.

## Code changes and helpdesk simulation

Remove self-service replacement, including replacement after email verification. A configured code cannot be regenerated through setup or the classroom demo preparation page. Users request changes through "Request security code change through helpdesk", available on the dashboard and the security code screen.

The helpdesk action displays a simulation acknowledgement that the request was received. It sends no real message, grants no sign-in approval, and does not change the code automatically. Actual helpdesk verification and rotation are outside the agreed popup-only simulation.

## Existing accounts

For accounts created under the earlier five-code prototype, retain the first remaining saved code as the permanent security code and retire the other hashes. Do not generate a new secret automatically. Accounts with no saved code complete first-time setup after email verification.

## Account protection

An incorrect security code follows the current wrong-number rule: immediate lockout, starting at two minutes and increasing by five minutes for subsequent lockouts, capped at sixty minutes. Successful sign-ins keep the existing strike-reset behavior. Active lockouts block both methods and cannot be bypassed by restarting, changing preferences, or requesting helpdesk assistance.

## Classroom demonstration

Demo mode provides a fictional prepared account with one reusable security code and a control to simulate email sending failure. Failed delivery does not count as an incorrect verification attempt. Demo controls are unavailable outside demo mode. A local PHP router protects runtime data and prevents missing-file requests from resetting sign-in state.

## Verification

- First-time setup generates exactly one hashed code and requires saving acknowledgement.
- Repeated and concurrent valid sign-ins keep the same code hash.
- Login preference works after either verification method.
- Switching cancels delayed email approval and retains browser ownership checks.
- Incorrect code entries keep the existing lockout and escalation.
- Helpdesk receipt and old self-service replacement submissions cannot change codes.
- Existing accounts retain their first remaining saved code.
- Preparing a demo account again cannot rotate its configured code.
- Demo controls remain restricted to demo mode.
