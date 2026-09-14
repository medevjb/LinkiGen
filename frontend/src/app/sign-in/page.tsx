"use client";

import { useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { apiFetch, ApiError } from "@/lib/api";
import type { User } from "@/lib/types";

function SignInForm() {
  const router = useRouter();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const { user } = await apiFetch<{ user: User }>("/api/v1/auth/login", {
        method: "POST",
        body: { email, password },
      });

      const next =
        {
          registered: "/onboarding/profile-import",
          profile_import: "/onboarding/profile-import",
          profile_review: "/onboarding/profile-review",
          goals: "/onboarding/goals",
          dashboard: "/dashboard",
        }[user.onboarding_status] ?? "/dashboard";

      router.push(next);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Something went wrong.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="w-full max-w-sm space-y-4">
      <h1 className="text-2xl font-semibold">Sign in</h1>

      {error && (
        <p className="text-sm text-red-400 bg-red-950/40 border border-red-900 rounded px-3 py-2">
          {error}
        </p>
      )}

      <div className="space-y-1">
        <label className="text-sm text-zinc-400">Email</label>
        <input
          type="email"
          className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 outline-none focus:border-zinc-600"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
      </div>

      <div className="space-y-1">
        <label className="text-sm text-zinc-400">Password</label>
        <input
          type="password"
          className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 outline-none focus:border-zinc-600"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
      </div>

      <button
        type="submit"
        disabled={submitting}
        className="w-full rounded-md bg-zinc-50 text-zinc-950 px-4 py-2 font-medium hover:bg-zinc-200 transition disabled:opacity-50"
      >
        {submitting ? "Signing in…" : "Sign in"}
      </button>

      <p className="text-sm text-zinc-500 text-center">
        No account yet?{" "}
        <Link href="/sign-up" className="text-zinc-300 underline">
          Create one
        </Link>
      </p>
    </form>
  );
}

export default function SignInPage() {
  return (
    <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50 px-6">
      <SignInForm />
    </main>
  );
}
