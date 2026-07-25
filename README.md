# Leaderboard (`local_leaderboard`) — Features

A gamification plugin for Moodle that turns learning activity into points, tiers, and competitive rankings.

---

## Points & rewards
- **Automatic point awarding** — no manual scoring; points are granted the moment a learner triggers a tracked event.
- **Four earning triggers out of the box** — starting/viewing an activity, completing an activity, viewing a course, and completing a course.
- **Duplicate-safe** — each rule awards a user only once per activity/course/event, so repeated views or re-completions never inflate scores.
- **Configurable point values** — every rule carries its own point amount, set by the admin.

## Flexible rule engine
- **Three scope levels** — define point rules platform-wide, per course, or per individual activity.
- **Smart precedence** — the most specific rule always wins (activity beats course, course beats platform), so you can set a broad default and override it selectively.
- **Activity-type aware** — rules bind to a course module and its type; the rule builder auto-detects the activity type as you select it.
- **Dynamic rule builder** — the admin form cascades course → activity dropdowns via live JavaScript, so only valid choices are ever shown.

## Levels & tiers
- **Custom tiers** — create any number of named levels (Bronze, Silver, Gold, etc.), each with its own point range.
- **Visual identity per tier** — each level has a colour and an optional uploaded badge image, with default badges included.
- **Automatic promotion** — a learner's tier is derived live from their total points; no manual assignment.
- **Ordered progression** — sort order and contiguous point ranges form a clear ladder, with validation to prevent overlaps and gaps.

## Referral rewards
- **Referral bonus points** — users who refer others earn a configurable bonus per successful referral, added on top of their activity points.

## Leaderboard display
- **Ranked leaderboard** — users sorted by total points, with ties broken by who reached the score first.
- **Two views** — a "Top 10" summary and a full leaderboard.
- **Tier filter tabs** — narrow the board to a single level, or view all levels together.
- **Country toggle** — switch between a global board and a country-scoped board.
- **Personal progress card** — each user sees their own rank, current tier, total points, and a progress bar toward the next tier.
- **Themable points icon** — the points icon and its colour come from admin settings (Font Awesome class + colour picker).

## Administration & integration
- **Dedicated admin area** — a settings page plus rule-management and level-management screens, grouped under Local plugins.
- **Role-based access** — separate `view` and `manage` capabilities; students and teachers see the board, while managers and admins configure it.
- **Navigation integration** — a Leaderboard link is added automatically for anyone with view access.
- **Mustache-templated UI** — presentation is fully template-driven and themeable.
- **Clean uninstall** — all plugin tables are dropped on removal.
