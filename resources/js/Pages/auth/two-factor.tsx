import type { Branding, Routes } from "../../types";

type TwoFactorProps = {
  callback_url?: string;
  routes?: Routes;
  branding?: Branding;
  csrf_token?: string;
};

export default function TwoFactor({
  callback_url,
  routes = {},
  branding = {},
  csrf_token,
}: TwoFactorProps) {
  return (
    <section className="caronte-auth">
      <div className="caronte-auth__panel">
        <span className="caronte-kicker">
          {branding.app_name || "Caronte"}
        </span>
        <h1 className="caronte-title">Two-factor sign in</h1>
        <p className="caronte-copy">
          We will send a secure login link to the email address registered in
          Caronte.
        </p>

        <div className="caronte-card">
          <div className="caronte-card__header">
            <h2>Request a sign-in link</h2>
            <p>Open the email on any device and follow the secure link.</p>
          </div>

          <form
            method="POST"
            action={routes.twoFactorRequest}
            className="caronte-form"
          >
            <input type="hidden" name="_token" value={csrf_token} />
            {callback_url ? (
              <input type="hidden" name="callback_url" value={callback_url} />
            ) : null}

            <div>
              <label htmlFor="email" className="form-label">
                Registered email
              </label>
              <input
                id="email"
                type="email"
                name="email"
                className="form-control"
                required
              />
            </div>

            <button type="submit" className="btn caronte-btn-primary">
              Send sign-in link
            </button>
          </form>

          <div className="caronte-card__footer">
            <a href={routes.passwordRecoverForm}>
              Need to reset your password first?
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}
