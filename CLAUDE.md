## Approach
- Read existing files before writing. Don't re-read unless changed.
- Thorough in reasoning, concise in output.
- Skip files over 100KB unless required.
- No sycophantic openers or closing fluff.
- No emojis or em-dashes.
- Do not guess APIs, versions, flags, commit SHAs, or package names. Verify by reading code or docs before asserting.

## Token efficiency
- Read only files directly needed. Ask before broad scans.
- Prefer reading specific functions/sections over full files.
- Do not restate context already established in conversation.
- Keep plans to 3-5 steps unless complexity requires more.
- For edits, show only the changed section, not full file.
- Default to brief answers. Expand only if asked.
- Reuse information already gathered unless files changed.