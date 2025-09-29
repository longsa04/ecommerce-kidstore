# QA: Session ID Regeneration After Login

This manual QA check ensures that the login flow regenerates the PHP session identifier, preventing fixation attacks.

1. Start from a fresh browser session (e.g., incognito window) and navigate to the login page.
2. Open developer tools and note the current value of the `PHPSESSID` cookie **before** logging in.
3. Log in with a valid customer or admin account.
4. After the page refreshes, inspect the cookies again and confirm that the `PHPSESSID` value has changed to a new identifier.
5. Log out and verify that a new session identifier is issued once more.

If the identifier does not change during steps 4 or 5, the regression has returned and must be investigated.
