# AGENTS.md

This file defines the working guidelines for AI coding agents working on the Almutqon project.

---

# 1. Project Identity

## Project

Almutqon Arabic Learning

Almutqon is an Arabic learning platform focused on practical, structured, academic, and accessible Arabic education.

The website should communicate:

- Arabic learning
- academic credibility
- warmth
- clarity
- modernity
- intentional design
- human craftsmanship

The interface should feel like a real educational product, not an automatically generated AI website.

---

# 2. Current Technology Stack

The current project uses:

- PHP
- HTML
- CSS
- Vanilla JavaScript
- MySQL

Development environment:

- Laravel Herd
- Dbngin
- Local development URL: `http://almutqon.test`

Important:

This project is currently NOT a React project.

Do NOT migrate or introduce:

- React
- Vue
- Laravel
- Tailwind
- Next.js
- other frameworks

unless the user explicitly requests it.

Prefer the existing architecture.

---

# 3. Core Working Philosophy

## Understand Before Editing

Do not immediately modify files when the user's request is ambiguous or affects multiple components.

Before making significant changes:

1. Understand the user's actual goal.
2. Inspect the relevant existing files.
3. Understand how the current implementation works.
4. Identify the actual cause of the problem.
5. Determine the smallest reasonable scope.
6. Create a plan.
7. Implement the plan.
8. Verify the result.

Do not modify code merely because it could theoretically be improved.

---

# 4. Interpret Human Language Carefully

The user may describe design or coding problems informally.

Examples:

- "Bikin lebih enak dilihat."
- "Ini terasa AI banget."
- "Kurang hidup."
- "Terlalu kosong."
- "Card-nya kurang pas."
- "Bikin lebih premium."
- "Jangan terlalu rame."

These statements are design goals, not literal implementation instructions.

When receiving an abstract request:

1. Determine what the user is probably trying to achieve.
2. Inspect the existing implementation.
3. Translate the request into concrete design or engineering requirements.
4. Avoid blindly applying trendy UI patterns.
5. Preserve the existing visual identity.

For non-trivial tasks, briefly explain the interpretation before implementation.

---

# 5. Prompt-to-Task Translation

When the user's request is vague, internally convert it into:

## Goal

What outcome does the user actually want?

## Scope

Which page, component, section, or functionality should change?

## Constraints

What must NOT change?

## Implementation

What is the smallest appropriate technical solution?

## Acceptance Criteria

How can we determine whether the task is actually complete?

Example:

User:

> "Navbar-nya kurang enak di mobile."

Translate into:

Goal:
Improve the mobile navbar experience.

Scope:
Navbar only.

Constraints:
- Do not change authentication.
- Do not change backend logic.
- Preserve desktop behavior.
- Preserve existing visual identity.
- Do not introduce a framework.

Acceptance criteria:
- Works at common mobile widths.
- No horizontal overflow.
- Navigation remains accessible.
- Menu interaction works correctly.
- Desktop layout remains intact.

---

# 6. Scope Control

Respect the scope of the request.

If the user asks to fix one component:

Do not automatically:

- redesign the entire page
- refactor unrelated CSS
- rename unrelated classes
- restructure the project
- replace existing components
- change backend logic
- install unnecessary dependencies

Prefer the smallest change that solves the actual problem.

If an unrelated issue is discovered, mention it separately instead of silently changing it.

---

# 7. Design Philosophy

Almutqon should feel:

- clean
- thoughtful
- educational
- modern
- human
- trustworthy
- visually balanced

Avoid making the interface look like a generic AI-generated SaaS template.

## Avoid excessive use of:

- gradients
- glassmorphism
- huge rounded cards
- excessive shadows
- floating blobs
- unnecessary glow effects
- excessive animations
- oversized headings
- repetitive cards
- generic dashboard layouts
- decorative elements without purpose

Modern does not mean adding more effects.

Good design should primarily come from:

- hierarchy
- spacing
- typography
- proportion
- alignment
- contrast
- composition
- consistency

---

# 8. Visual Identity

The primary visual direction uses **forest green** (`#166534`) with amber accents (`#F59E0B`). This palette evokes growth, nature, and warmth—appropriate for an educational platform.

Do not randomly replace or introduce colors.

Before adding a new color:

1. Check the existing design system in `css/base.css`.
2. Reuse existing CSS custom properties (`--forest`, `--primary`, `--accent`) when possible.
3. Ensure sufficient contrast, especially in dark mode.
4. Keep visual hierarchy clear.

Do not create a new color palette for a small component unless necessary.

---

# 9. UI/UX Principles

Prioritize:

### Hierarchy

Users should immediately understand:

1. What this page/section is.
2. What information matters.
3. What action they can take.

### Spacing

Spacing should feel intentional.

Avoid:

- cramped layouts
- excessive empty space
- inconsistent gaps
- arbitrary margins

### Alignment

Elements should share meaningful alignment lines.

Avoid visually random positioning.

### Proportion

Cards, buttons, inputs, headings, and sections should have believable proportions.

Avoid making every component excessively rounded or oversized.

### Consistency

If a component already exists elsewhere, reuse its design language.

Do not create a completely different version of the same component without a reason.

---

# 10. Responsive Design

The website must work across:

- mobile
- tablet
- laptop
- desktop

Pay particular attention to:

- 320px
- 375px
- 390px
- 430px

Mobile is not simply a smaller desktop.

Check:

- navigation
- text wrapping
- button width
- form layout
- card dimensions
- section padding
- image scaling
- overflow
- touch targets

Never fix mobile by intentionally breaking desktop.

Avoid horizontal scrolling unless it is explicitly part of the design.

---

# 11. HTML Rules

Prefer semantic HTML where practical.

Use appropriate elements such as:

- `header`
- `nav`
- `main`
- `section`
- `article`
- `footer`
- `button`
- `form`
- `label`

Avoid unnecessary wrapper elements.

Preserve existing IDs and classes when they are used by JavaScript or backend logic.

Before renaming an element, search for references to it.

---

# 12. CSS Rules

Prefer modifying the existing CSS architecture instead of creating duplicate styles.

Before adding CSS:

1. Search for existing related selectors.
2. Reuse existing variables and patterns.
3. Check whether the problem is caused by specificity or inheritance.
4. Avoid unnecessary `!important`.

Do not create multiple competing rules for the same component.

Avoid fixing a problem by stacking increasingly specific selectors.

If a CSS section becomes difficult to understand, improve it carefully without changing unrelated behavior.

---

# 13. JavaScript Rules

Preserve existing functionality.

Before changing JavaScript:

- identify event listeners
- identify DOM dependencies
- identify API calls
- identify state or variables used elsewhere

Do not rewrite working JavaScript simply for stylistic reasons.

When fixing JavaScript:

- handle expected errors
- avoid unnecessary global variables
- avoid duplicate event listeners
- verify behavior after changes

---

# 14. PHP Rules

Preserve existing backend behavior unless backend changes are explicitly requested.

Pay attention to:

- authentication
- sessions
- database queries
- validation
- authorization
- API endpoints
- redirects

Never casually modify authentication or authorization logic while working on frontend tasks.

Never expose:

- passwords
- API keys
- secrets
- environment variables
- credentials

---

# 15. Database Rules

Do not modify database structure unless explicitly required.

Before changing a database-related implementation:

1. Inspect the existing schema.
2. Understand dependencies.
3. Check existing queries.
4. Consider backwards compatibility.

Do not delete columns, tables, or data without explicit authorization.

---

# 16. File Organization

Keep files reasonably maintainable.

Avoid creating:

- unnecessarily huge files
- duplicate CSS files
- duplicate JavaScript
- unnecessary components
- temporary files inside the project

If a file becomes excessively large, consider splitting it logically.

Do not split files merely for the sake of splitting them.

---

# 17. Dependencies

Do not install a dependency unless it provides meaningful value.

Before adding a dependency:

1. Check whether the functionality can be implemented using the existing stack.
2. Consider maintenance cost.
3. Consider bundle/performance impact.
4. Consider whether the project actually needs it.

Prefer native browser functionality when practical.

---

# 18. Existing Code Has Priority

The existing project is the source of truth.

Do not assume:

- the project uses a framework
- a component works a certain way
- a class has a certain purpose
- a database field exists
- a route exists
- a dependency is installed

Inspect the code before making assumptions.

---

# 19. Browser Verification

When browser testing is available, use it when appropriate.

Verify:

- page loads
- console errors
- layout
- interactions
- responsive behavior
- forms
- navigation
- buttons
- dynamic content

For visual changes, inspect the actual rendered result rather than relying only on source code.

If Playwright is available, prefer it for repeatable browser verification.

---

# 20. Error Handling

When an error occurs:

Do not immediately patch the visible symptom.

Instead:

1. Read the complete error.
2. Identify where it originates.
3. Trace the relevant code path.
4. Determine the root cause.
5. Apply the smallest correct fix.
6. Re-test.

Do not hide errors simply to make the console appear clean.

---

# 21. Debugging

Use evidence.

Prefer:

- reading the actual file
- searching references
- checking browser console
- checking network requests
- checking server logs
- reproducing the problem
- testing the affected behavior

Avoid speculative changes.

Do not say something is fixed unless it has been verified or there is a clear reason it could not be verified.

---

# 22. Large Tasks

For complex tasks, use this workflow:

### Phase 1 — Inspect

Understand the current implementation.

### Phase 2 — Plan

Define:

- objective
- affected files
- dependencies
- implementation steps
- risks
- acceptance criteria

### Phase 3 — Implement

Make focused changes.

### Phase 4 — Verify

Test the actual behavior.

### Phase 5 — Review

Check for:

- regressions
- unnecessary changes
- responsive issues
- console errors
- duplicated code

Then stop.

Do not continue "improving" the project indefinitely.

---

# 23. Small Tasks

For small, obvious tasks:

- inspect the relevant code
- make the minimal change
- verify it
- report the result

Do not create unnecessary planning overhead.

---

# 24. When to Ask the User

Ask for clarification when:

- two interpretations would produce significantly different results
- the requested change could cause destructive consequences
- important business logic is missing
- credentials or sensitive configuration are required
- a decision cannot reasonably be inferred from the existing project

Do NOT ask unnecessary questions when the project already provides enough context.

Use reasonable defaults when safe.

---

# 25. Communication Style

Communicate clearly and concisely.

When explaining technical problems:

- use simple language
- explain the actual cause
- avoid unnecessary jargon
- distinguish facts from assumptions

When reporting completed work, include:

1. What changed.
2. Which files changed.
3. How it was verified.
4. Any remaining limitations.

Do not claim tests were run if they were not actually run.

---

# 26. AI Coding Behavior

The agent is an implementation assistant, not an autonomous redesign system.

Do not optimize for:

"change as much as possible."

Optimize for:

"solve the user's actual problem with the smallest appropriate change."

When uncertain:

- inspect first
- reason from the existing code
- preserve existing behavior
- avoid unnecessary changes

---

# 27. Definition of Done

A task is complete when:

- the requested behavior exists
- existing functionality still works
- relevant errors are resolved
- responsive behavior is acceptable
- unnecessary changes were avoided
- the implementation matches the project's existing design and architecture
- the result has been verified when verification is possible

Once these conditions are met, stop.

Do not introduce additional changes merely because they could theoretically be improved.

---

# 28. Priority Order

When instructions conflict, prioritize:

1. Explicit user request
2. Safety and security
3. Existing project architecture
4. These project guidelines
5. General coding conventions
6. Personal assumptions

Never override an explicit user requirement merely because another implementation is preferred.

---

# 29. Final Principle

Build Almutqon as if it were being maintained by a real product team.

The goal is not:

"Make the AI generate code."

The goal is:

"Use AI to help build a coherent, maintainable, human-designed product."

Think before editing.

Inspect before assuming.

Change only what is necessary.

Verify before declaring success.
