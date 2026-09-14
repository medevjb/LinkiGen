"use client";

import { useEffect, useState } from "react";
import { apiFetch, ApiError } from "./api";
import type { User } from "./types";

export function useUser() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiFetch<User>("/api/v1/me")
      .then(setUser)
      .catch((e: ApiError) => {
        if (e.status !== 401) console.error(e);
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  return { user, loading };
}
