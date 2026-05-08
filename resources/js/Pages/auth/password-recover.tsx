import type { Branding, Routes } from "../../types";

type PasswordRecoverProps = {
  routes?: Routes;
  branding?: Branding;
  csrf_token?: string;
};

export default function PasswordRecover({
  routes = {},
  branding = {},
  csrf_token,
}: PasswordRecoverProps) {
  return (
    <section className="caronte-auth">
      <div className="caronte-auth__panel">
        <span className="caronte-kicker">
          {branding.app_name || "Caronte"}
        </span>
        <h1 className="caronte-title">Choose a new password</h1>
        <p className="caronte-copy">
          Your reset token is valid. Set a new password to continue.
        </p>

        <div className="caronte-card">
          <div className="caronte-card__header">
            <h2>Reset password</h2>
            <p>Use at least eight characters.</p>
          </div>

          <form
            method="POST"
            action={routes.passwordRecoverSubmit}
            className="caronte-form"
          >
            <input type="hidden" name="_token" value={csrf_token} />

            <div>
              <label htmlFor="password" className="form-label">
                New password
              </label>
              <input
                id="password"
                type="password"
                name="password"
                className="form-control"
                required
              />
            </div>

            <div>
              <label htmlFor="password_confirmation" className="form-label">
                Confirm password
              </label>
              <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                className="form-control"
                required
              />
            </div>

            <button type="submit" className="btn caronte-btn-primary">
              Update password
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
