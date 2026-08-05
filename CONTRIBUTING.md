# Contributing to Rosetta

Thanks for contributing! Rosetta is built so that people writing in almost
any language have a natural way in - most of what follows applies no matter
what stack you're bringing.

## Claiming an issue

To avoid duplicate work:

1. Comment on the issue you want to work to claim it (e.g. "claiming this").
2. You have **48 hours** from your claim comment to open a pull request
   (a draft PR is fine - it just needs to exist and reference the issue).
3. If 48 hours pass with no PR, the issue is considered released and anyone
   else can claim it. A maintainer may leave a comment un-assigning you, but
   don't take it personally - just re-claim it if you're still working on it
   and need more time, ideally before the window closes.

Issues are labelled by difficulty (`difficulty:easy`, `difficulty:medium`,
`difficulty:hard`) and area (`area:core`, `area:adapter`, `area:tests`,
`area:docs`) - filter by whichever matches your comfort level and stack.

## Development setup

You'll need Node.js 18+ always, plus whichever language toolchain your
change touches (PHP 8+ and/or Go 1.21+ for the existing reference adapters).

```bash
git clone git@github.com:freeCodeCamp-Summer-Cohort-2026/rosetta.git
cd rosetta
npm test          # runs the full suite, including both reference adapters
node core/cli.js list
```

## Adding a brand-new adapter in a language we don't have yet

This is the highest-leverage way to contribute if your language of choice
isn't already represented under `adapters/`. Steps:

1. **Read the contract.** [`docs/ADAPTER_CONTRACT.md`](docs/ADAPTER_CONTRACT.md)
   is the spec for everything below - read it fully before writing code.
2. **Create a directory:** `adapters/<your-language>/` (e.g. `adapters/rust/`).
3. **Implement the `convert` operation:**
   - Read a single JSON request object from stdin:
     `{"operation": "convert", "input": "...", "options": {"from": "...", "to": "..."}}`.
   - Support all four case styles as `to` targets: `snake`, `camel`,
     `pascal`, `kebab`.
   - Write a single JSON response object to stdout:
     `{"output": "...", "error": null}` on success, or
     `{"output": null, "error": "<message>"}` on a handled failure.
   - Exit with code `0` in both cases above. Only use a non-zero exit code
     for a genuine crash you can't recover from.
   - Send any debug/log output to stderr, never stdout.
4. **Add a manifest:** `adapters/<your-language>/adapter.json` with at least
   `name` and `run` (see the manifest table in
   [`docs/ADAPTER_CONTRACT.md`](docs/ADAPTER_CONTRACT.md#2-the-manifest-adapterjson)).
   Pick a `name` that doesn't collide with an existing adapter.
5. **Add a test:** extend [`test/adapters.test.js`](test/adapters.test.js)
   (or add a new file under `test/`) that calls `findAdapter` +
   `runAdapter` against your new adapter and asserts on at least one real
   conversion in each direction, plus one handled-error case (e.g. an
   unsupported `to` value or empty input). Model it on the existing PHP/Go
   tests.
6. **Update the reference table** in [`README.md`](README.md#reference-adapters)
   to list your adapter.
7. Open a PR. CI (`.github/workflows/ci.yml`) will set up Node, PHP, and Go
   and run the full test suite - if your language needs a toolchain CI
   doesn't already install, add the relevant setup step to the workflow as
   part of your PR.

## Other kinds of contributions

Plenty of issues touch the core CLI (`core/`), tests (`test/`), or docs
without needing a new adapter at all - see the `area:core`, `area:tests`,
and `area:docs` labels. These are a good fit if you want to work in
JavaScript/Node, or if your language of choice already has a reference
adapter and you'd rather improve the orchestration layer.

## Code style

Keep it boring and readable - this repo is a teaching tool as much as a
working tool. Prefer standard-library solutions over new dependencies
unless an issue specifically calls for a package.
