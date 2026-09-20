# Contributing with GitHub Desktop: Forking, Branching and Pull Requests

A beginner-friendly guide to making changes to this repo
(https://github.com/mrwhinna-mw/hearback_wordpress) using GitHub Desktop.

## Key concepts

**Repository (repo)** – A project folder whose full change history is tracked by Git.

**Git vs. GitHub vs. GitHub Desktop**
- *Git* is the version-control tool that tracks changes on your computer.
- *GitHub* is the website that hosts repos online so people can share and collaborate.
- *GitHub Desktop* is a point-and-click app for using Git with GitHub, so you don't need the command line.

**Clone** – Download a copy of an online repo to your computer. Your copy stays linked to the online one.

**Fork** – Your own personal copy of someone else's repo, stored under *your* GitHub account. Use a fork when you don't have write access to the original (the "upstream" repo). You can change your fork freely without affecting the original, then propose your changes back via a pull request. If you are a collaborator with write access to the repo, you can skip forking and just branch.

**Branch** – A parallel line of work inside a repo. The default branch is usually `main` and should hold stable, working code. A branch lets you experiment or build a feature without touching `main`. If it goes well, you merge it in; if not, you delete it with no harm done.

**Commit** – A saved snapshot of your changes with a short message describing them. Think of it as a checkpoint you can return to.

**Push** – Send your local commits up to GitHub.

**Fetch / Pull** – Fetch checks GitHub for new changes; pull downloads and applies them to your local copy.

**Pull request (PR)** – A request to merge your branch into another branch (usually `main`). It's where the owner reviews, comments on and approves the changes.

**Merge** – Combining the changes from one branch into another.

### How it fits together

```
Original repo on GitHub (upstream: mrwhinna-mw/hearback_wordpress)
      |  Fork (only if you lack write access)
      v
Your fork on GitHub (origin)
      |  Clone
      v
Your computer  ->  create branch  ->  edit  ->  commit
      |  Push
      v
Your fork on GitHub  ->  Pull request  ->  Original repo `main`
```

## Step-by-step

### Prerequisites
1. Create a free account at https://github.com.
2. Install GitHub Desktop from https://desktop.github.com and sign in (**File > Options > Accounts**).

### 1. Fork the repo (skip if you have write access)
1. Go to https://github.com/mrwhinna-mw/hearback_wordpress.
2. Click **Fork** (top right), then **Create fork**.
3. You now have `github.com/<your-username>/hearback_wordpress`.

### 2. Clone it with GitHub Desktop
1. In GitHub Desktop: **File > Clone repository**.
2. Choose the **GitHub.com** tab and select `hearback_wordpress` (your fork, or the original if you have access).
3. Pick a local folder and click **Clone**.
4. If you cloned a fork, GitHub Desktop asks how you'll use it. Choose **To contribute to the parent project**. This keeps your fork linked to the original.

### 3. Create a branch
1. Make sure **Current Branch** (top bar) shows `main`.
2. Click **Current Branch > New Branch**.
3. Name it descriptively, e.g. `add-branching-guide` (lowercase, hyphens, no spaces).
4. Confirm **Create branch based on `main`** is selected, then click **Create Branch**.
5. The top bar now shows your new branch. Everything you do from here stays on it.

### 4. Make your changes
1. Click **Repository > Show in Explorer** (or **Open in Visual Studio Code**).
2. Add or edit files, e.g. add a new Markdown file. Save.
3. Back in GitHub Desktop, changed files appear in the **Changes** tab with a diff on the right (green = added, red = removed).

### 5. Commit
1. Tick the files you want to include.
2. Bottom-left, enter a **Summary** (e.g. "Add GitHub Desktop branching guide") and optionally a description.
3. Click **Commit to `<your-branch>`**.

### 6. Publish / push the branch
1. Click **Publish branch** (first time) or **Push origin** (later commits).
2. Your branch is now on GitHub.

### 7. Open a pull request
1. Click **Create Pull Request** (or **Branch > Create Pull Request**). Your browser opens on GitHub.
2. Check the base repo/branch (`mrwhinna-mw/hearback_wordpress` : `main`) and your compare branch.
3. Add a title and description explaining what and why, then click **Create pull request**.

### 8. Review and merge
1. The repo owner reviews, may request changes, and then merges.
2. To change your PR, commit more on the same branch and push. The PR updates automatically.

### 9. Clean up and stay in sync
1. After merging, switch to `main` via **Current Branch**.
2. Click **Fetch origin**, then **Pull origin** to get the latest.
3. Delete the finished branch: **Branch > Delete** (GitHub also offers a "Delete branch" button after merge).
4. If you use a fork, GitHub Desktop shows a banner to **Merge from upstream** (or use **Branch > Update from main**) to keep your fork current with the original repo.

## Tips
- One branch per task or feature keeps PRs small and easy to review.
- Commit often with clear messages.
- Always create a new branch from an up-to-date `main`.
- Never work directly on `main`.
- **Merge conflicts** happen when two branches edit the same lines. GitHub Desktop will point out the conflicted files and offer to open them in your editor so you can choose what to keep.
- Use **History** to see past commits and **Branch > Discard all changes** to undo uncommitted edits.

## Quick glossary

| Term | Meaning |
|------|---------|
| origin | Your remote copy on GitHub (your fork, or the original if you cloned it directly) |
| upstream | The original repo you forked from |
| HEAD | The commit/branch you're currently on |
| main | The default, stable branch |
| PR | Pull request: a proposal to merge a branch |
