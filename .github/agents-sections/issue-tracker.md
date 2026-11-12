# Issue tracker: GitHub

https://github.com/lipemat/go-live-update-urls-pro/issues

Tickets and plans for this repo live as GitHub issues in the PRO plugin's repo, `lipemat/go-live-update-urls-pro`. Use the `gh` CLI for all operations. This clone's remote is `lipemat/go-live-update-urls`, so `gh` cannot infer the repo: pass `--repo lipemat/go-live-update-urls-pro` to every `gh issue` and `gh label` command.

## Lifecycle

Every issue and ticket carries exactly one lifecycle state, applied as a label of the same name:

| State | Meaning | Set by |
| --- | --- | --- |
| `ready` | Available to pick up. | `plan-save`, `plan-to-tickets` |
| `in-progress` | Being implemented right now. | `plan-implement` |
| `pending-qa` | Ready for qa. Not a blocker. | `plan-implement` |

Moving an item to a state means removing the state label it currently holds and adding the new one. Moving a ticket to `pending-qa` also means ticking every satisfied acceptance-criteria checkbox in the ticket body.

`pending-qa` is terminal for the skills. Release is manual, so no skill closes an issue or moves it past this state; closing after release is the human's call.

## Conventions

- Use the `gh issue` commands with `--repo lipemat/go-live-update-urls-pro` for all operations (create, view, list, edit, comment, close, labels, `--parent` for sub-issues).
- When editing a body to check off acceptance criteria, preserve the rest of the body.

## When a skill says "publish to the issue tracker"

Create a GitHub issue in `lipemat/go-live-update-urls-pro`.

## When a skill says "link the branch to the issue"

GitHub's API can only link a branch while creating it (`createLinkedBranch`), so create the remote branch through the issue: `gh issue develop <number> --repo lipemat/go-live-update-urls-pro --name <branch> --base develop`. The local branch of the same name then pushes onto it.

## When a skill says "fetch the relevant ticket"

Run `gh issue view <number> --repo lipemat/go-live-update-urls-pro`. When the ticket is a sub-issue, fetch its parent the same way.

## GitHub Project

Issue status is managed in GitHub project **10** (owner `lipemat`), in its `Status` field. Any time an issue's lifecycle state changes, update both its label and its status in the project.

Each lifecycle state maps to one status option:

| Lifecycle state | Project status |
| --- | --- |
| `ready` | Ready |
| `in-progress` | In progress |
| `pending-qa` | Pending QA |

The project's other statuses (Backlog, Pending release, Done) are managed by hand; no skill sets them.

### Updating the status of an issue

```bash
gh project item-edit 10 --owner lipemat --url <issue url> --field Status --value "<project status>"
```

Success prints nothing and exits 0; trust it, no need to re-check. Any failure prints an error and exits 1. An issue not yet in the project fails; add it with `gh project item-add` and retry.
