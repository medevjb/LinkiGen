import Link from "next/link";

export default function LandingPage() {
  return (
    <main className="flex-1 flex items-center justify-center bg-zinc-950 text-zinc-50 px-6">
      <div className="max-w-xl text-center space-y-6">
        <p className="text-sm uppercase tracking-widest text-zinc-500">
          JB AI Career Agent
        </p>
        <h1 className="text-4xl font-semibold tracking-tight">
          Let your AI Career Agent handle the repetitive work.
        </h1>
        <p className="text-zinc-400">
          Understand your professional profile once. Get a weekly plan,
          LinkedIn content, and job matches prepared for you automatically.
        </p>
        <Link
          href="/sign-up"
          className="inline-block rounded-md bg-zinc-50 text-zinc-950 px-6 py-3 font-medium hover:bg-zinc-200 transition"
        >
          Build My Career Profile
        </Link>
      </div>
    </main>
  );
}
