"use client";

import { useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";

export default function ProfileImportPage() {
  const router = useRouter();
  const [url, setUrl] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onImport(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await apiFetch("/api/v1/profile/import", {
        method: "POST",
        body: { linkedin_url: url },
      });
      router.push("/onboarding/profile-review");
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

  async function onManual() {
    setError(null);
    setSubmitting(true);
    try {
      await apiFetch("/api/v1/profile/import/manual", { method: "POST" });
      router.push("/onboarding/profile-review");
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
    <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50 px-6">
      <div className="w-full max-w-md space-y-6">
        <div className="space-y-2">
          <h1 className="text-2xl font-semibold">Build your Career Profile</h1>
          <p className="text-zinc-400 text-sm">
            Paste your LinkedIn profile URL. We&apos;ll research your
            publicly available professional information and prepare a draft
            profile for you to review — nothing is used until you confirm it.
          </p>
        </div>

        {error && (
          <p className="text-sm text-red-400 bg-red-950/40 border border-red-900 rounded px-3 py-2">
            {error}
          </p>
        )}

        <form onSubmit={onImport} className="space-y-3">
          <input
            type="url"
            placeholder="https://www.linkedin.com/in/your-name"
            className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 outline-none focus:border-zinc-600"
            value={url}
            onChange={(e) => setUrl(e.target.value)}
            required
          />
          <button
            type="submit"
            disabled={submitting}
            className="w-full rounded-md bg-zinc-50 text-zinc-950 px-4 py-2 font-medium hover:bg-zinc-200 transition disabled:opacity-50"
          >
            {submitting ? "Starting research…" : "Build My Career Profile"}
          </button>
        </form>

        <div className="flex items-center gap-3 text-zinc-600 text-xs">
          <div className="h-px bg-zinc-800 flex-1" />
          or
          <div className="h-px bg-zinc-800 flex-1" />
        </div>

        <button
          onClick={onManual}
          disabled={submitting}
          className="w-full rounded-md border border-zinc-800 px-4 py-2 font-medium hover:bg-zinc-900 transition disabled:opacity-50"
        >
          Skip and enter my profile manually
        </button>
      </div>
    </main>
  );
}
