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

**Security code**:
A code saved in advance that can approve one sign-in as the user's chosen verification method or as backup when email is unavailable. A successfully used security code cannot approve another sign-in.
_Avoid_: Recovery code, email number, reusable backup password

**Sign-in preference**:
The verification method a user chooses for future sign-ins, subject to account protection and the availability of unused security codes.

**Helpdesk recovery**:
An assisted recovery route for a user who cannot access either primary email verification or saved security codes. This academic prototype demonstrates only a simulated acknowledgement of the user's request.
