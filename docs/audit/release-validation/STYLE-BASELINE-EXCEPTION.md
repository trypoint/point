# PHP-CS-Fixer baseline exception

This exception applies only to the v0.1.0 Stable candidate based on RC.1 commit `5fd8606406bec3aeb6639179c4667af79cae2b05`. The following counts include the four candidate PHP changes and the added XML security test file.

Using PHP 8.5.10, PHP-CS-Fixer 3.95.32, and the unchanged `.cs.php` configuration, a clean RC.1 worktree reports 104 of 104 scanned files. The Stable candidate reports 100 of 105 files. The candidate's five changed PHP files report zero violations.

The baseline reports `line_ending` and `align_multiline_comment` violations, with `method_argument_space` violations in some files. Git's `core.autocrlf=true` contributes the line-ending results. The same settings reproduce the failures on the clean baseline. The XML runtime changes and added regression coverage introduce no new style violations.

The modified-file PHP-CS-Fixer check passes. The full-tree style check remains failed with exit code 8 because the pre-existing baseline violations remain. This exception records that result. It does not suppress the check, change the fixer configuration, or mark the global style gate as passing.

## Maintainer acceptance — 2026-11-28

**ACCEPTED FOR v0.1.0 ONLY.** The project maintainer explicitly accepted this pre-existing global PHP-CS-Fixer baseline debt, while requiring the full-tree command to remain reported as **FAIL**. This acceptance does not turn the global style gate into PASS.

The clean RC.1 baseline reported 104/104 files; the candidate reported 100/105; all five modified PHP files passed. Do not weaken `.cs.php`, hide the global failure, or mass-reformat unrelated sources for this exception. No automatic exception applies to future versions.

Approver: project maintainer (explicit decision in conversation on 2026-11-28). This acceptance does not approve any other outstanding release gate.
