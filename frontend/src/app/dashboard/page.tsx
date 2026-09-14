"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { apiFetch } from "@/lib/api";
import { useUser } from "@/lib/useUser";
import type { CareerProfile, WeeklyPlan, Task } from "@/lib/types";

export default function DashboardPage() {
  const router = useRouter();
  const { user, loading: userLoading } = useUser();
  const [profile, setProfile] = useState<CareerProfile | null>(null);
  const [plan, setPlan] = useState<WeeklyPlan | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (userLoading) return;
    if (!user) {
      router.push("/sign-in");
      return;
    }

    Promise.all([
      apiFetch<{ career_profile: CareerProfile | null }>("/api/v1/profile"),
      apiFetch<{ weekly_plan: WeeklyPlan }>("/api/v1/plans/current"),
    ])
      .then(([p, pl]) => {
        setProfile(p.career_profile);
        setPlan(pl.weekly_plan);
      })
      .finally(() => setLoading(false));
  }, [user, userLoading, router]);

  async function setTaskStatus(task: Task, status: Task["status"]) {
    const { task: updated } = await apiFetch<{ task: Task }>(
      `/api/v1/tasks/${task.id}`,
      { method: "PATCH", body: { status } }
    );
    setPlan((p) =>
      p ? { ...p, tasks: p.tasks.map((t) => (t.id === updated.id ? updated : t)) } : p
    );
  }

  if (userLoading || loading) {
    return (
      <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50">
        <p className="text-zinc-500">Loading your dashboard…</p>
      </main>
    );
  }

  const completed = plan?.tasks.filter((t) => t.status === "completed").length ?? 0;
  const total = plan?.tasks.length ?? 0;

  return (
    <main className="flex-1 bg-zinc-950 text-zinc-50 px-6 py-10">
      <div className="max-w-3xl mx-auto space-y-10">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold">
              Welcome back, {user?.name.split(" ")[0]}
            </h1>
            <p className="text-zinc-400 text-sm">What matters today.</p>
          </div>
          <button
            onClick={() =>
              apiFetch("/api/v1/auth/logout", { method: "POST" }).then(() =>
                router.push("/sign-in")
              )
            }
            className="text-sm text-zinc-500 hover:text-zinc-300"
          >
            Sign out
          </button>
        </div>

        <section className="grid grid-cols-2 gap-4">
          <div className="rounded-lg border border-zinc-800 p-4">
            <p className="text-zinc-500 text-sm">Career Score</p>
            <p className="text-3xl font-semibold mt-1">
              {profile?.completeness_score ?? 0}%
            </p>
          </div>
          <div className="rounded-lg border border-zinc-800 p-4">
            <p className="text-zinc-500 text-sm">Weekly Progress</p>
            <p className="text-3xl font-semibold mt-1">
              {completed}/{total}
            </p>
          </div>
        </section>

        <section className="space-y-3">
          <h2 className="text-lg font-medium">Today&apos;s Actions</h2>
          {!plan || plan.tasks.length === 0 ? (
            <p className="text-zinc-500 text-sm">
              Your first weekly plan is being prepared.
            </p>
          ) : (
            <ul className="space-y-2">
              {plan.tasks.map((task) => (
                <li
                  key={task.id}
                  className={`rounded-lg border border-zinc-800 p-4 ${
                    task.status === "completed" ? "opacity-50" : ""
                  }`}
                >
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="font-medium">{task.title}</p>
                      {task.description && (
                        <p className="text-sm text-zinc-400 mt-1">
                          {task.description}
                        </p>
                      )}
                      {task.rationale && (
                        <p className="text-xs text-zinc-600 mt-2">
                          Why: {task.rationale}
                        </p>
                      )}
                    </div>
                    <div className="flex flex-col gap-1 shrink-0">
                      {task.status !== "completed" && (
                        <button
                          onClick={() => setTaskStatus(task, "completed")}
                          className="text-xs rounded border border-zinc-700 px-2 py-1 hover:bg-zinc-900"
                        >
                          Complete
                        </button>
                      )}
                      {task.status === "to_do" && (
                        <button
                          onClick={() => setTaskStatus(task, "skipped")}
                          className="text-xs rounded border border-zinc-800 px-2 py-1 text-zinc-500 hover:bg-zinc-900"
                        >
                          Skip
                        </button>
                      )}
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="grid grid-cols-2 gap-4">
          <div className="rounded-lg border border-zinc-800 p-4">
            <p className="text-zinc-500 text-sm mb-1">Upcoming Content</p>
            <p className="text-sm text-zinc-400">
              Your next content ideas are being prepared.
            </p>
          </div>
          <div className="rounded-lg border border-zinc-800 p-4">
            <p className="text-zinc-500 text-sm mb-1">Job Matches</p>
            <p className="text-sm text-zinc-400">
              Automatic job discovery isn&apos;t set up yet. Analyze a job
              manually any time.
            </p>
          </div>
        </section>
      </div>
    </main>
  );
}
