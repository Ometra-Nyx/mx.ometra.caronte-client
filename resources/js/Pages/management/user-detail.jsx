import React from "react";

export default function UserDetail({
  user = {},
  assigned_role_uris = [],
  configured_roles = [],
  features = {},
  routes = {},
  csrf_token,
}) {
  return (
    <div className="container py-4 py-lg-5">
      <div className="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
          <a href={routes.dashboard} className="caronte-backlink">
            &larr; Back to user management
          </a>
          <h1 className="caronte-title mt-2 mb-2">{user.name || "User"}</h1>
          <p className="caronte-copy mb-0">{user.email || ""}</p>
        </div>

        <form method="POST" action={routes.delete}>
          <input type="hidden" name="_token" value={csrf_token} />
          <input type="hidden" name="_method" value="DELETE" />
          <button type="submit" className="btn btn-outline-danger">
            Delete user
          </button>
        </form>
      </div>

      <div className="row g-4">
        <div className="col-12 col-xl-6">
          <div className="caronte-card h-100">
            <div className="caronte-card__header">
              <h2>User profile</h2>
              <p>
                Update the display name without leaving the current management context.
              </p>
            </div>

            <form method="POST" action={routes.update} className="caronte-form">
              <input type="hidden" name="_token" value={csrf_token} />
              <input type="hidden" name="_method" value="PUT" />

              <div>
                <label htmlFor="name" className="form-label">
                  Display name
                </label>
                <input
                  id="name"
                  type="text"
                  name="name"
                  defaultValue={user.name || ""}
                  className="form-control"
                  required
                />
              </div>

              <div>
                <label className="form-label">Email</label>
                <input type="email" value={user.email || ""} className="form-control" disabled />
              </div>

              <button type="submit" className="btn caronte-btn-primary">
                Save profile
              </button>
            </form>
          </div>
        </div>

        <div className="col-12 col-xl-6">
          <div className="caronte-card h-100">
            <div className="caronte-card__header">
              <h2>Application roles</h2>
              <p>Roles are sourced from local config and synchronized as a final set.</p>
            </div>

            <form method="POST" action={routes.syncRoles} className="caronte-form">
              <input type="hidden" name="_token" value={csrf_token} />
              <input type="hidden" name="_method" value="PUT" />

              <div className="caronte-checkbox-list">
                {configured_roles.map((role) => (
                  <label className="caronte-checkbox" key={role.uri_applicationRole}>
                    <input
                      type="checkbox"
                      name="roles[]"
                      value={role.uri_applicationRole}
                      defaultChecked={assigned_role_uris.includes(role.uri_applicationRole)}
                    />
                    <span>
                      <strong>{role.name}</strong>
                      <small>{role.description}</small>
                    </span>
                  </label>
                ))}
              </div>

              <button type="submit" className="btn caronte-btn-primary">
                Synchronize role set
              </button>
            </form>
          </div>
        </div>

        {features.metadata ? (
          <div className="col-12">
            <div className="caronte-card">
              <div className="caronte-card__header">
                <h2>Metadata</h2>
                <p>Attach application-scoped metadata entries to this user.</p>
              </div>

              <div className="row g-4">
                <div className="col-12 col-lg-6">
                  <form method="POST" action={routes.storeMetadata} className="caronte-form">
                    <input type="hidden" name="_token" value={csrf_token} />

                    <div>
                      <label htmlFor="key" className="form-label">
                        Key
                      </label>
                      <input id="key" type="text" name="key" className="form-control" required />
                    </div>

                    <div>
                      <label htmlFor="value" className="form-label">
                        Value
                      </label>
                      <textarea id="value" name="value" rows="4" className="form-control" />
                    </div>

                    <button type="submit" className="btn caronte-btn-primary">
                      Store metadata
                    </button>
                  </form>
                </div>

                <div className="col-12 col-lg-6">
                  <h3 className="h6 mb-3">Current entries</h3>
                  {(user.metadata || []).length === 0 ? (
                    <p className="text-muted mb-0">
                      No metadata entries are currently stored for this user.
                    </p>
                  ) : (
                    (user.metadata || []).map((item) => (
                      <form
                        key={item.key}
                        method="POST"
                        action={routes.deleteMetadata}
                        className="d-flex justify-content-between align-items-start gap-3 border rounded p-3 mb-3"
                      >
                        <input type="hidden" name="_token" value={csrf_token} />
                        <input type="hidden" name="_method" value="DELETE" />
                        <input type="hidden" name="key" value={item.key} />
                        <div>
                          <div className="fw-semibold">{item.key}</div>
                          <small className="text-muted">{item.value}</small>
                        </div>
                        <button type="submit" className="btn btn-sm btn-outline-danger">
                          Delete
                        </button>
                      </form>
                    ))
                  )}
                </div>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
