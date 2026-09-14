"use client";

import { useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";

const GOALS = [
  { value: "grow_linkedin", label: "Grow my LinkedIn presence" },
  { value: "find_job", label: "Find a better job" },
  { value: "both", label: "Both" },
] as const;

const DIRECTIONS = [
  { value: "ic", label: "Individual contributor" },
  { value: "leadership", label: "Leadership" },
  { value: "freelance", label: "Freelance / consulting" },
  { value: "not_sure", label: "Not sure" },
] as const;

export default function GoalsPage() {
  const router = useRouter();
  const [primaryGoal, setPrimaryGoal] = useState<string>("both");
  const [targetRole, setTargetRole] = useState("");
  const [location, setLocation] = useState("");
  const [direction, setDirection] = useState("not_sure");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await apiFetch("/api/v1/goals", {
        method: "PUT",
        body: {
          primary_goal: primaryGoal,
          target_roles: targetRole ? [targetRole] : [],
          locations: location ? [location] : [],
          career_direction: direction,
        },
      });
      router.push("/dashboard");
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
      <form onSubmit={onSubmit} className="w-full max-w-md space-y-6">
        <h1 className="text-2xl font-semibold">What are you focused on?</h1>

        {error && (
          <p className="text-sm text-red-400 bg-red-950/40 border border-red-900 rounded px-3 py-2">
            {error}
          </p>
        )}

        <div className="space-y-2">
          {GOALS.map((g) => (
            <label
              key={g.value}
              className={`flex items-center gap-3 rounded-md border px-3 py-2 cursor-pointer ${
                primaryGoal === g.value
                  ? "border-zinc-400 bg-zinc-900"
                  : "border-zinc-800"
              }`}
            >
              <input
                type="radio"
                name="primary_goal"
                value={g.value}
                checked={primaryGoal === g.value}
                onChange={() => setPrimaryGoal(g.value)}
              />
              {g.label}
            </label>
          ))}
        </div>

        <div className="space-y-1">
          <label className="text-sm text-zinc-400">Target role (optional)</label>
          <input
            className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm outline-none focus:border-zinc-600"
            value={targetRole}
            onChange={(e) => setTargetRole(e.target.value)}
            placeholder="e.g. Staff Engineer"
          />
        </div>

        <div className="space-y-1">
          <label className="text-sm text-zinc-400">Preferred location (optional)</label>
          <input
            className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm outline-none focus:border-zinc-600"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
            placeholder="e.g. Remote, Berlin"
          />
        </div>

        <div className="space-y-1">
          <label className="text-sm text-zinc-400">Career direction</label>
          <select
            className="w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm outline-none focus:border-zinc-600"
            value={direction}
            onChange={(e) => setDirection(e.target.value)}
          >
            {DIRECTIONS.map((d) => (
              <option key={d.value} value={d.value}>
                {d.label}
              </option>
            ))}
          </select>
        </div>

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-md bg-zinc-50 text-zinc-950 px-4 py-2 font-medium hover:bg-zinc-200 transition disabled:opacity-50"
        >
          {submitting ? "Saving…" : "Start My Career Agent"}
        </button>
      </form>
    </main>
  );
}
