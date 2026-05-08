export type Branding = {
  app_name?: string;
  headline?: string;
  subheadline?: string;
  accent?: string;
};

export type Routes = Record<string, string | undefined>;

export type TenantOption = {
  tenant_id: string;
  name: string;
};

export type Role = {
  uri_applicationRole: string;
  name: string;
  description?: string;
};

export type UserMetadata = {
  key: string;
  value?: string;
};

export type User = {
  uri_user?: string;
  name?: string;
  email?: string;
  metadata?: UserMetadata[];
};

export type Paginated<T> = {
  data?: T[];
  links?: unknown[];
};

export type FeatureFlags = {
  metadata?: boolean;
};
