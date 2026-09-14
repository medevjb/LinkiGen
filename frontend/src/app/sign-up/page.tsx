"use client";

import { useState, useEffect, useRef, FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { apiFetch, ApiError } from "@/lib/api";
import type { User } from "@/lib/types";

const RESEND_COOLDOWN_SECONDS = 30;

export default function SignUpPage() {
  const router = useRouter();
  const [step, setStep] = useState<"form" | "otp">("form");

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await apiFetch("/api/v1/auth/register", {
        method: "POST",
        body: { name, email, password, password_confirmation: passwordConfirmation },
      });
      setStep("otp");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Something went wrong.");
    } finally {
      setSubmitting(false);
    }
  }

  if (step === "otp") {
    return <OtpStep email={email} onVerified={() => router.push("/onboarding/profile-import")} />;
  }

  return (
    <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50 px-6">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-4">
        <h1 className="text-2xl font-semibold">Create your account</h1>

        {error && (
          <p className="text-sm text-red-400 bg-red-950/40 border border-red-900 rounded px-3 py-2">
            {error}
          </p>
        )}

        <div className="space-y-1">
          <label className="text-sm text-zinc-400">Name</label>
          <input
            className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 outline-none focus:border-zinc-600"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
        </div>

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
            minLength={8}
          />
        </div>

        <div className="space-y-1">
          <label className="text-sm text-zinc-400">Confirm password</label>
          <input
            type="password"
            className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 outline-none focus:border-zinc-600"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            required
          />
        </div>

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-md bg-zinc-50 text-zinc-950 px-4 py-2 font-medium hover:bg-zinc-200 transition disabled:opacity-50"
        >
          {submitting ? "Creating account…" : "Create account"}
        </button>

        <p className="text-sm text-zinc-500 text-center">
          Already have an account?{" "}
          <Link href="/sign-in" className="text-zinc-300 underline">
            Sign in
          </Link>
        </p>
      </form>
    </main>
  );
}

function OtpStep({ email, onVerified }: { email: string; onVerified: () => void }) {
  const [code, setCode] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);
  const [verifying, setVerifying] = useState(false);
  const [resending, setResending] = useState(false);
  const [cooldown, setCooldown] = useState(RESEND_COOLDOWN_SECONDS);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    inputRef.current?.focus();
  }, []);

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setInterval(() => setCooldown((c) => c - 1), 1000);
    return () => clearInterval(t);
  }, [cooldown]);

  async function verify(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setVerifying(true);
    try {
      await apiFetch<{ user: User }>("/api/v1/auth/verify-email/otp", {
        method: "POST",
        body: { email, code },
      });
      onVerified();
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
        if (e.code === "OTP_ATTEMPTS_EXCEEDED" || e.code === "OTP_EXPIRED") {
          setCode("");
        }
      } else {
        setError("Something went wrong.");
      }
    } finally {
      setVerifying(false);
    }
  }

  async function resend() {
    setError(null);
    setInfo(null);
    setResending(true);
    try {
      await apiFetch("/api/v1/auth/verify-email/resend", {
        method: "POST",
        body: { email },
      });
      setInfo("A new code is on its way.");
      setCode("");
      setCooldown(RESEND_COOLDOWN_SECONDS);
      inputRef.current?.focus();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Something went wrong.");
    } finally {
      setResending(false);
    }
  }

  return (
    <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50 px-6">
      <form onSubmit={verify} className="w-full max-w-sm space-y-4 text-center">
        <h1 className="text-2xl font-semibold">Check your email</h1>
        <p className="text-zinc-400 text-sm">
          We sent a 6-digit code to <strong>{email}</strong>.
        </p>

        {error && (
          <p className="text-sm text-red-400 bg-red-950/40 border border-red-900 rounded px-3 py-2">
            {error}
          </p>
        )}
        {info && (
          <p className="text-sm text-emerald-400 bg-emerald-950/40 border border-emerald-900 rounded px-3 py-2">
            {info}
          </p>
        )}

        <input
          ref={inputRef}
          inputMode="numeric"
          autoComplete="one-time-code"
          maxLength={6}
          placeholder="000000"
          className="w-full text-center text-3xl tracking-[0.5em] font-mono rounded-md bg-zinc-900 border border-zinc-800 px-3 py-3 outline-none focus:border-zinc-600"
          value={code}
          onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
          required
        />

        <button
          type="submit"
          disabled={verifying || code.length !== 6}
          className="w-full rounded-md bg-zinc-50 text-zinc-950 px-4 py-2 font-medium hover:bg-zinc-200 transition disabled:opacity-50"
        >
          {verifying ? "Verifying…" : "Verify"}
        </button>

        <button
          type="button"
          onClick={resend}
          disabled={resending || cooldown > 0}
          className="w-full rounded-md border border-zinc-800 px-4 py-2 text-sm hover:bg-zinc-900 transition disabled:opacity-40 disabled:hover:bg-transparent"
        >
          {resending
            ? "Sending…"
            : cooldown > 0
              ? `Resend code in ${cooldown}s`
              : "Resend code"}
        </button>
      </form>
    </main>
  );
}
