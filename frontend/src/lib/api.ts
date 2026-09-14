const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";

export class ApiError extends Error {
  code: string;
  status: number;
  details?: Record<string, unknown>;

  constructor(code: string, message: string, status: number, details?: Record<string, unknown>) {
    super(message);
    this.code = code;
    this.status = status;
    this.details = details;
  }
}

function readCookie(name: string): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
  return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Always fetches a fresh CSRF cookie rather than reusing one already in the
 * browser. A cookie's mere presence doesn't mean it still matches a live
 * server-side session — a backend restart, session-store change, or
 * migrate:fresh invalidates existing sessions without touching the client's
 * cookie jar, and a stale-but-present token used to slip past this check
 * and cause a CSRF mismatch on the next mutating request.
 */
async function ensureCsrfCookie(): Promise<void> {
  await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: "include" });
}

/**
 * Wraps Sanctum's stateful SPA auth (session cookie + CSRF token) per
 * 08-api-contract.md §2. GET requests never need the CSRF dance; anything
 * state-changing does.
 */
export async function apiFetch<T>(
  path: string,
  options: { method?: string; body?: unknown } = {}
): Promise<T> {
  const method = options.method ?? "GET";

  if (method !== "GET") {
    await ensureCsrfCookie();
  }

  const headers: Record<string, string> = {
    Accept: "application/json",
  };

  if (options.body !== undefined) {
    headers["Content-Type"] = "application/json";
  }

  const xsrf = readCookie("XSRF-TOKEN");
  if (xsrf) {
    headers["X-XSRF-TOKEN"] = xsrf;
  }

  const res = await fetch(`${API_URL}${path}`, {
    method,
    credentials: "include",
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : null;

  if (!res.ok) {
    const err = data?.error;
    throw new ApiError(
      err?.code ?? "UNKNOWN_ERROR",
      err?.message ?? data?.message ?? "Something went wrong.",
      res.status,
      err?.details
    );
  }

  return data as T;
}
