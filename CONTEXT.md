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
A permanent, reusable code saved in advance for sign-in, either as the user's preferred verification method or as backup when email is unavailable. Changes require a helpdesk request.
_Avoid_: Single-use recovery code, email number

**Sign-in preference**:
The verification method a user chooses for future sign-ins, subject to account protection and completion of security code setup.

**Helpdesk recovery**:
An assisted route for a user who has forgotten their security code or wants it changed. This academic prototype demonstrates only a simulated acknowledgement of the user's request.
