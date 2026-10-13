# Proposed helpdesk recovery policy

## Status and prototype boundary

This records the recovery design discussed for the academic prototype. It is a proposed policy for a future institutional deployment, not an implemented identity-checking service or a guarantee against impersonation. The current helpdesk button only shows a simulated receipt acknowledgement; it does not verify identity, change the security code, or approve sign-in.

## Lost-phone scenario

If email access and the saved security code are both on the lost phone, the user may lose both verification options. A permanent code is a reusable secret and does not automatically provide an independent authentication factor. The prototype also does not check the displayed password field.

Users should keep their saved code securely outside the same phone where practical. A separately enrolled device or security key could provide an independent recovery option in a future implementation; neither is implemented here.

## Separate verification

Separate verification means proving identity without relying on the lost phone, the inaccessible email account, or personal facts an attacker might know. If a previously enrolled independent authentication method is still available, it can be verified. If all methods are lost, the proposed school recovery route is an in-person visit to the IT office.

## Proposed in-person identity checks

1. Require the requester to appear in person at the school IT office.
2. Compare the requester with an original school or government photo ID.
3. Independently check the institution's existing photo and enrollment records through authorized staff access.
4. Have a second authorized reviewer approve a high-risk reset, recording the reason and the checks performed.

A name, birthday, student number, security-question answer, or an emailed ID photograph alone is insufficient proof. An original ID also is not a guarantee: the independent record check and reviewer are additional controls, not claims of perfect identity verification.

## Controls around an approved change

Only an approved recovery process should authorize code replacement. A future implementation should record requests and approvals, notify previously registered channels, invalidate the old code and existing sign-in sessions, and securely enroll the replacement method. Notifications are alerts, not identity proof. Staff should follow a consistent process and be trained to recognize social engineering and pressure to skip checks.

## Reference

[OWASP Multifactor Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Multifactor_Authentication_Cheat_Sheet.html) identifies recovery/reset paths as potential attack paths and calls for rigorous identity verification when support assists recovery. The specific in-person workflow above is our proposed institutional policy, not an OWASP-mandated checklist.
