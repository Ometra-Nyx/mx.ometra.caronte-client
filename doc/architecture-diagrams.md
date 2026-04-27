# Architecture Diagrams

## 1. System Context (C4 Level 1)

```mermaid
C4Context
    title System Context — Caronte Client Package

    Person(endUser, "End User", "Authenticates via browser")
    Person(developer, "Developer / Admin", "Manages users and roles via CLI or Management UI")

    System(hostApp, "Host Laravel Application", "Any Laravel app that includes ometra/caronte-client")
    System_Ext(caronteServer, "Caronte Server", "Central authentication authority; issues and validates JWTs")
    SystemDb(localDb, "Local Database", "Optional mirror of user data (CaronteUser / CaronteUserMetadata)")
    System_Ext(mailService, "Mail Service", "Delivers 2FA codes and password-recovery emails")

    Rel(endUser, hostApp, "Authenticates, requests pages")
    Rel(developer, hostApp, "Manages users/roles via Management UI or Artisan CLI")
    Rel(hostApp, caronteServer, "Validates/exchanges JWTs; syncs roles; manages users", "HTTPS + X-Application-Token")
    Rel(hostApp, localDb, "Reads/writes user mirror (optional)")
    Rel(hostApp, mailService, "Sends 2FA and recovery emails (when delivery=host)")
    Rel(caronteServer, mailService, "Sends 2FA and recovery emails (when delivery=server)")
```

---

## 2. Container Diagram (C4 Level 2)

```mermaid
flowchart TD
    Browser["Browser (End User)"]
    CLI["Developer CLI"]

    subgraph Host["Host Laravel Application"]
        WebLayer["Web Layer\n(Routes + Middleware)"]
        Package["caronte-client Package\n(Controllers, Commands, Core)"]
    end

    DB["Local Database\n(Users / UsersMetadata)"]
    CaronteServer["Caronte Server\n(Auth Authority)"]
    Mail["Mail Service"]

    Browser -- "HTTP / HTTPS" --> WebLayer
    CLI -- "php artisan caronte:*" --> Package
    WebLayer --> Package
    Package -- "PDO (optional)" --> DB
    Package -- "HTTPS + X-Application-Token" --> CaronteServer
    Package -- "SMTP / Mailable (optional)" --> Mail
    CaronteServer -- "JWT in response" --> Package
```

---

## 3. Component Diagram

```mermaid
flowchart LR
    subgraph Middleware
        VS[ValidateSession]
        VR[ValidateRoles]
        RAT[ResolveApplicationToken]
        RTC[ResolveTenantContext]
    end

    subgraph Controllers
        Auth[AuthController]
        Mgmt[ManagementController]
        User[UserController]
        Role[RoleController]
    end

    subgraph Core
        Facade[Caronte Facade]
        Token[CaronteToken]
        Req[CaronteRequest]
        RM[CaronteRoleManager]
    end

    subgraph ApiClients
        HTTP[CaronteHttpClient]
        ClientApi[ClientApi]
        RoleApi[RoleApi]
    end

    subgraph Support
        AppToken[ApplicationToken]
        ConfigRoles[ConfiguredRoles]
        ReqCtx[RequestContext]
        TCR[TenantContextResolver]
        PH[PermissionHelper]
    end

    subgraph Models
        CUser[CaronteUser]
        CMeta[CaronteUserMetadata]
    end

    VS --> Facade
    VS --> Token
    VS --> PH
    VR --> PH
    RAT --> AppToken
    RTC --> TCR

    Auth --> Req
    Auth --> Facade
    Mgmt --> ClientApi
    User --> ClientApi
    Role --> RM

    Facade --> Token
    Req --> HTTP
    RM --> RoleApi
    RM --> ConfigRoles
    RM --> AppToken

    HTTP --> ClientApi
    HTTP --> RoleApi

    Token --> HTTP

    Facade --> CUser
    Facade --> CMeta
```

---

## 4. Per-Request JWT Validation Sequence

```mermaid
sequenceDiagram
    participant Browser
    participant ValidateSession
    participant Caronte as Caronte (Facade)
    participant CaronteToken
    participant Session
    participant CaronteServer

    Browser->>ValidateSession: HTTP request
    ValidateSession->>Caronte: getToken()
    Caronte->>Session: read caronte.user_token
    Session-->>Caronte: JWT string (or null)
    Caronte-->>ValidateSession: JWT string

    ValidateSession->>CaronteToken: validateToken(jwt)
    CaronteToken->>CaronteToken: assertSignatureAndIssuer()

    alt Token is valid
        CaronteToken-->>ValidateSession: true
        ValidateSession->>ValidateSession: checkApplicationAccess()
        ValidateSession-->>Browser: pass through to controller
    else Token is expired
        CaronteToken->>CaronteToken: exchangeToken(jwt)
        CaronteToken->>CaronteServer: POST api/auth/exchange
        CaronteServer-->>CaronteToken: new JWT
        CaronteToken->>Caronte: saveToken(newJwt)
        Caronte->>Session: store new token
        CaronteToken-->>ValidateSession: true (with X-User-Token header set)
        ValidateSession-->>Browser: pass through + X-User-Token response header
    else Token invalid / missing
        CaronteToken-->>ValidateSession: false
        ValidateSession-->>Browser: redirect to /login (or 401 for API)
    end
```

---

## 5. User Authentication Sequence

```mermaid
sequenceDiagram
    participant Browser
    participant AuthController
    participant CaronteRequest
    participant CaronteHttpClient
    participant CaronteServer
    participant Caronte as Caronte (Facade)
    participant Session

    Browser->>AuthController: POST /login {email, password}
    AuthController->>CaronteRequest: userPasswordLogin(email, password)
    CaronteRequest->>CaronteHttpClient: authRequest(POST, api/auth/login, …)
    CaronteHttpClient->>CaronteServer: POST api/auth/login
    CaronteServer-->>CaronteHttpClient: {status: 200, data: {token: "eyJ…"}}
    CaronteHttpClient-->>CaronteRequest: response array
    CaronteRequest-->>AuthController: JWT string

    AuthController->>Caronte: saveToken(jwt)
    Caronte->>Session: store caronte.user_token = jwt
    AuthController-->>Browser: redirect to SUCCESS_URL
```
