"use client";

import { useEffect, useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import type { CareerProfile } from "@/lib/types";

export default function ProfileReviewPage() {
  const router = useRouter();
  const [profile, setProfile] = useState<CareerProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const [company, setCompany] = useState("");
  const [title, setTitle] = useState("");

  function load() {
    apiFetch<{ career_profile: CareerProfile | null }>("/api/v1/profile")
      .then((d) => setProfile(d.career_profile))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  async function updateField(field: string, value: string) {
    setSaving(true);
    try {
      const { career_profile } = await apiFetch<{ career_profile: CareerProfile }>(
        "/api/v1/profile",
        { method: "PATCH", body: { [field]: value } }
      );
      setProfile(career_profile);
    } finally {
      setSaving(false);
    }
  }

  async function addExperience(e: FormEvent) {
    e.preventDefault();
    if (!company || !title) return;
    setSaving(true);
    try {
      await apiFetch("/api/v1/profile/experience", {
        method: "POST",
        body: { company, title, is_current: true },
      });
      setCompany("");
      setTitle("");
      load();
    } finally {
      setSaving(false);
    }
  }

  async function confirm() {
    setError(null);
    setSaving(true);
    try {
      await apiFetch("/api/v1/profile/confirm", { method: "POST" });
      router.push("/onboarding/goals");
    } catch (e) {
      if (e instanceof ApiError) {
        setError(
          e.details?.missing_fields
            ? `${e.message} Missing: ${(e.details.missing_fields as string[]).join(", ")}`
            : e.message
        );
      } else {
        setError("Something went wrong.");
      }
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return (
      <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50">
        <p className="text-zinc-500">Loading your profile…</p>
      </main>
    );
  }

  if (!profile) {
    return (
      <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50">
        <p className="text-zinc-500">No profile found. Start import first.</p>
      </main>
    );
  }

  const isResearching = profile.research_status === "running";
  const researchFailed = ["failed", "no_data"].includes(profile.research_status);

  return (
    <main className="flex-1 bg-zinc-950 text-zinc-50 px-6 py-10">
      <div className="max-w-2xl mx-auto space-y-8">
        <div>
          <h1 className="text-2xl font-semibold">Review your Career Profile</h1>
          <p className="text-zinc-400 text-sm mt-1">
            Completeness: {profile.completeness_score}%
          </p>
        </div>

        {isResearching && (
          <p className="text-sm text-amber-300 bg-amber-950/30 border border-amber-900 rounded px-3 py-2">
            Structuring your experience and skills… refresh in a moment.
          </p>
        )}
        {researchFailed && (
          <p className="text-sm text-zinc-400 bg-zinc-900 border border-zinc-800 rounded px-3 py-2">
            We couldn&apos;t research this profile automatically ({profile.research_error_code}).
            Fill in the fields below manually — everything still works.
          </p>
        )}
        {error && (
          <p className="text-sm text-red-400 bg-red-950/40 border border-red-900 rounded px-3 py-2">
            {error}
          </p>
        )}

        <div className="space-y-4">
          <Field
            label="Headline"
            defaultValue={profile.headline ?? ""}
            onBlurSave={(v) => updateField("headline", v)}
          />
          <Field
            label="Current role"
            defaultValue={profile.current_role ?? ""}
            onBlurSave={(v) => updateField("current_role", v)}
          />
          <Field
            label="Current company"
            defaultValue={profile.current_company ?? ""}
            onBlurSave={(v) => updateField("current_company", v)}
          />
          <Field
            label="Location"
            defaultValue={profile.location ?? ""}
            onBlurSave={(v) => updateField("location", v)}
          />
          <Field
            label="Professional focus"
            defaultValue={profile.professional_focus ?? ""}
            onBlurSave={(v) => updateField("professional_focus", v)}
            textarea
          />
        </div>

        <div className="space-y-3">
          <h2 className="text-lg font-medium">Experience</h2>
          <ul className="space-y-2">
            {profile.experiences?.map((exp) => (
              <li
                key={exp.id}
                className="rounded-md border border-zinc-800 px-3 py-2 text-sm"
              >
                <span className="font-medium">{exp.title}</span> at {exp.company}
                {exp.is_current && (
                  <span className="ml-2 text-xs text-emerald-400">current</span>
                )}
              </li>
            ))}
          </ul>

          <form onSubmit={addExperience} className="flex gap-2">
            <input
              placeholder="Title"
              className="flex-1 rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm outline-none focus:border-zinc-600"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
            <input
              placeholder="Company"
              className="flex-1 rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm outline-none focus:border-zinc-600"
              value={company}
              onChange={(e) => setCompany(e.target.value)}
            />
            <button
              type="submit"
              className="rounded-md border border-zinc-800 px-3 py-2 text-sm hover:bg-zinc-900"
            >
              Add
            </button>
          </form>
        </div>

        <button
          onClick={confirm}
          disabled={saving}
          className="w-full rounded-md bg-zinc-50 text-zinc-950 px-4 py-2 font-medium hover:bg-zinc-200 transition disabled:opacity-50"
        >
          {saving ? "Saving…" : "Confirm Profile"}
        </button>
      </div>
    </main>
  );
}

function Field({
  label,
  defaultValue,
  onBlurSave,
  textarea,
}: {
  label: string;
  defaultValue: string;
  onBlurSave: (value: string) => void;
  textarea?: boolean;
}) {
  const [value, setValue] = useState(defaultValue);

  const common = {
    className:
      "w-full rounded-md bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm outline-none focus:border-zinc-600",
    value,
    onChange: (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
      setValue(e.target.value),
    onBlur: () => {
      if (value !== defaultValue) onBlurSave(value);
    },
  };

  return (
    <div className="space-y-1">
      <label className="text-sm text-zinc-400">{label}</label>
      {textarea ? <textarea rows={3} {...common} /> : <input {...common} />}
    </div>
  );
}
