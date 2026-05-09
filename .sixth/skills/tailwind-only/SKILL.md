---
name: tailwind-only
description: >
  Use this skill whenever the user asks to build UI, style components, write HTML,
  or work on Blade/React/Vue files in this project. Enforces Tailwind CSS utilities
  exclusively — no custom CSS, no inline styles, no <style> blocks. Trigger this
  even if the user doesn't say "Tailwind" explicitly, as long as they want any kind
  of styling, layout, or visual work done.
---

# Tailwind CSS Only

All styling must use Tailwind CSS utility classes exclusively. Never produce custom
CSS or inline styles under any circumstance.

## Project Theme

This project has custom Tailwind colors defined in `tailwind.config.js`. Always
prefer these over arbitrary values:

- **Primary** (warm orange-brown): `primary-50` through `primary-950`
- **Accent** (olive green): `accent-50` through `accent-950`

For example, use `bg-primary-500` instead of `bg-orange-500`, and `text-accent-700`
instead of a custom green color.

## Rules

- Use only Tailwind utility classes directly on HTML/Blade/JSX elements
- Never write CSS inside `<style>` blocks
- Never use inline `style=""` attributes
- Never create or modify `.css` files with custom class definitions
- If a one-off value is truly needed (e.g., a very specific pixel size), use
  Tailwind's arbitrary value syntax: `w-[340px]`, `mt-[3px]` — not inline styles

## Last Resort: @apply

If a pattern is repeated many times and extracting a component isn't possible,
you may use `@apply` inside `resources/css/app.css`. Always prefer utility classes
directly on elements first.

## Translating Design Requests

When the user describes a visual goal, map it to Tailwind utilities:

- "Make it blue with padding" → `class="bg-blue-500 p-4"`
- "Centered card with shadow" → `class="mx-auto rounded-lg shadow-md"`
- "Red error text, small" → `class="text-red-500 text-sm"`
- "Brand color button" → `class="bg-primary-500 hover:bg-primary-600 text-white"`

## What to Avoid

| ❌ Wrong | ✅ Correct |
|---|---|
| `style="color: red"` | `class="text-red-500"` |
| `style="margin: 10px"` | `class="m-2.5"` or `class="m-[10px]"` |
| `<style>.foo { padding: 1rem }</style>` | `class="p-4"` |
| `background-color: #c96f4a` | `class="bg-primary-500"` |
