---
name: tailwind-only
description: >
  Use this skill whenever the user asks to build UI, style components, write HTML,
  or work on Blade/React/Vue files in this project. Prioritizes Tailwind CSS v4 utilities
  over custom CSS or inline styles. Trigger this even if the user doesn't say "Tailwind"
  explicitly, as long as they want any kind of styling, layout, or visual work done.
---

# Tailwind CSS v4 Development

Prioritize Tailwind CSS utility classes over custom CSS. Use custom CSS in `resources/css/app.css`
only when necessary for complex patterns or project-specific styling.

## Project Theme

This project uses **Ocean Blue & Teal** palette defined in `resources/css/app.css` using
Tailwind v4 `@theme` directive. Always prefer these over arbitrary values:

- **Primary** (Ocean Blue): `primary-50` through `primary-950` (#2563eb base)
- **Accent** (Teal): `accent-50` through `accent-950` (#14b8a6 base)
- **Success**: `success-50` through `success-950` (Teal)
- **Warning**: `warning-50` through `warning-950` (Amber)
- **Danger**: `danger-50` through `danger-950` (Rose Red)

For example, use `bg-primary-500` instead of `bg-blue-500`, and `text-accent-600`
instead of `text-teal-600`.

## Tailwind v4 Syntax

This project uses Tailwind CSS v4 with `@import 'tailwindcss'` and `@theme` directive.
Configuration is done in `resources/css/app.css` using the `@theme` block, not `tailwind.config.js`.

## Rules

- **First choice**: Use Tailwind utility classes directly on HTML/Blade/JSX elements
- **Avoid**: Inline `style=""` attributes (use Tailwind arbitrary values instead)
- **Avoid**: CSS inside `<style>` blocks in Blade files
- **When needed**: Add custom CSS in `resources/css/app.css` for complex patterns
- **Arbitrary values**: Use Tailwind's syntax for one-off values: `w-[340px]`, `mt-[3px]`

## Custom CSS in app.css

This project has custom CSS in `resources/css/app.css` for:
- Color variable mappings and aliases
- Page-specific component styles (e.g., `.home-page .hero-section`)
- Utility classes with semantic names (e.g., `.text-primary`, `.bg-accent`)

Add new custom CSS only when:
- A pattern is repeated many times and can't be extracted to a component
- Complex animations or specific page layouts are needed
- Working with existing custom CSS classes in the project

## Spacing Best Practices

When listing items, use gap utilities for spacing instead of margins:

```html
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

## Translating Design Requests

When the user describes a visual goal, map it to Tailwind utilities:

- "Make it ocean blue with padding" → `class="bg-primary-500 p-4"`
- "Centered card with shadow" → `class="mx-auto rounded-lg shadow-md"`
- "Teal accent text, small" → `class="text-accent-600 text-sm"`
- "Brand color button" → `class="bg-primary-500 hover:bg-primary-600 text-white"`

## Common Patterns

### Flexbox Layout
```html
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
```

### Grid Layout
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
```

## What to Avoid

| ❌ Wrong | ✅ Correct |
|---||
| `style="color: red"` | `class="text-danger-500"` |
| `style="margin: 10px"` | `class="m-2.5"` or `class="m-[10px]"` |
| `<style>.foo { padding: 1rem }</style>` | `class="p-4"` |
| `background-color: #2563eb` | `class="bg-primary-500"` |
| `margin-left: 1rem` on siblings | `gap-4` on parent flex/grid |
