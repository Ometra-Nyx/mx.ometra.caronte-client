import type { Branding, Routes } from "../../types";

type PasswordRecoverRequestProps = {
  routes?: Routes;
  branding?: Branding;
  csrf_token?: string;
};

export default function PasswordRecoverRequest({
  routes = {},
  branding = {},
  csrf_token,
}: PasswordRecoverRequestProps) {
  return (
    <section className="caronte-auth">
      <div className="caronte-auth__panel">
        <span className="caronte-kicker">
          {branding.app_name || "Caronte"}
        </span>
        <h1 className="caronte-title">Recover access</h1>
        <p className="caronte-copy">
          Enter your email and we will send password reset instructions.
        </p>

        <div className="caronte-card">
          <div className="caronte-card__header">
            <h2>Password recovery</h2>
            <p>If the account exists, a recovery message will be sent immediately.</p>
          </div>

          <form
            method="POST"
            action={routes.passwordRecoverRequest}
            className="caronte-form"
          >
            <input type="hidden" name="_token" value={csrf_token} />

            <div>
              <label htmlFor="email" className="form-label">
                Email
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
              Send recovery instructions
            </button>
          </form>

          <div className="caronte-card__footer">
            <a href={routes.login}>Back to sign in</a>
          </div>
        </div>
      </div>
    </section>
  );
}
