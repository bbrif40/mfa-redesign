# Academic MFA prototype

A demonstration of institutional sign-in verification and the experience of using an alternative verification method.

## Language

**Primary verification**:
The email number-matching verification normally used to approve a sign-in.

**Backup verification**:
An alternative verification method available when email delivery fails, the verification email does not arrive, or the user cannot access their inbox.
_Avoid_: MFA bypass, automatic approval

**Security lockout**:
A temporary account protection period caused by verification attempts, during which backup verification cannot bypass the protection.

**Recovery code**:
A code saved in advance that can approve one sign-in through backup verification. A successfully used recovery code cannot approve another sign-in.
_Avoid_: Email number, reusable backup password

**Helpdesk recovery**:
An assisted recovery route for a user who cannot access either primary email verification or saved recovery codes. This academic prototype demonstrates only a simulated acknowledgement of the user's request.
