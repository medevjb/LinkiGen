export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  timezone: string;
  onboarding_status: string;
}

export interface CareerProfile {
  id: number;
  headline: string | null;
  location: string | null;
  current_role: string | null;
  current_company: string | null;
  professional_summary: string | null;
  professional_focus: string | null;
  completeness_score: number;
  confirmed_at: string | null;
  research_status: string;
  research_error_code: string | null;
  experiences?: Experience[];
  educations?: Education[];
  skills?: { id: number; name: string }[];
}

export interface Experience {
  id: number;
  company: string;
  title: string;
  is_current: boolean;
  start_date: string | null;
  end_date: string | null;
}

export interface Education {
  id: number;
  institution: string;
  degree: string | null;
  field_of_study: string | null;
}

export interface CareerGoal {
  id: number;
  primary_goal: "grow_linkedin" | "find_job" | "both";
  target_roles_json: string[];
  career_direction: string | null;
}

export interface Task {
  id: number;
  type: string;
  title: string;
  description: string | null;
  priority: string;
  estimated_minutes: number | null;
  status: "to_do" | "in_progress" | "completed" | "skipped";
  rationale: string | null;
  scheduled_for: string | null;
}

export interface WeeklyPlan {
  id: number;
  week_start: string;
  week_end: string;
  status: string;
  tasks: Task[];
}
