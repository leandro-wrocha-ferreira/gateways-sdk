#!/usr/bin/env python3
"""
Deterministic Git Gatekeeper Hook for Antigravity — gateways-sdk
Enforces strict workflow boundaries: only authorized agents (/git-committer)
can execute mutating git commands (commit, push, add, merge, rebase, reset).
"""

import sys
import json
import os
import re
import shlex

MUTATING_GIT_COMMANDS = {
    'commit',
    'push',
    'add',
    'merge',
    'rebase',
    'cherry-pick',
    'tag',
    'reset',
}

READ_ONLY_GIT_COMMANDS = {
    'status',
    'diff',
    'log',
    'show',
    'branch',
    'rev-parse',
    'remote',
    'describe',
    'config',
    'checkout',
    'switch',
}

AUTHORIZED_ENV_VARS = [
    'GIT_AGENT=git-committer',
    'AGENT_ROLE=git-committer',
    'ALLOW_GIT_COMMIT=1',
]


def is_authorized_by_env_or_cmd(command_line: str) -> bool:
    """Check if the command line carries the authorized committer signature."""
    # Check in active process environment
    if os.environ.get('GIT_AGENT') == 'git-committer':
        return True
    if os.environ.get('AGENT_ROLE') == 'git-committer':
        return True
    if os.environ.get('ALLOW_GIT_COMMIT') == '1':
        return True

    # Check if prefixed in the command line itself (e.g. GIT_AGENT=git-committer git-leandro commit ...)
    for auth_token in AUTHORIZED_ENV_VARS:
        if auth_token in command_line:
            return True

    return False


def is_authorized_by_transcript(transcript_path: str) -> bool:
    """Inspect transcript to check if the active conversation or latest user prompt invoked /git-committer."""
    if not transcript_path or not os.path.isfile(transcript_path):
        return False

    try:
        with open(transcript_path, 'r', encoding='utf-8') as transcript_file:
            # Read last 50 lines to find the latest user interaction
            lines = transcript_file.readlines()
            recent_lines = lines[-50:] if len(lines) > 50 else lines

            for line in reversed(recent_lines):
                line = line.strip()
                if not line:
                    continue
                try:
                    step = json.loads(line)
                    # Check if user invoked /git-committer
                    if step.get('type') == 'USER_INPUT':
                        content = step.get('content', '')
                        if '/git-committer' in content:
                            return True
                        # If the user explicitly invoked another workflow, deny
                        if '/code-implementer' in content or '/code-reviewer' in content:
                            return False
                except Exception:
                    continue
    except Exception:
        return False

    return False


def extract_git_subcommands(command_line: str):
    """
    Parse command line (which may contain pipes, chaining &&, ||, ;)
    and yield any git / git-leandro subcommands found.
    """
    # Split by chain operators: &&, ||, ;, |
    sub_commands = re.split(r'&&|\|\||;|\|', command_line)
    git_subcommands = []

    for sub_cmd in sub_commands:
        sub_cmd = sub_cmd.strip()
        if not sub_cmd:
            continue

        try:
            tokens = shlex.split(sub_cmd)
        except Exception:
            tokens = sub_cmd.split()

        # Filter out env variable assignments at start (e.g. FOO=bar git ...)
        cmd_tokens = []
        for token in tokens:
            if '=' in token and not cmd_tokens:
                continue
            cmd_tokens.append(token)

        if not cmd_tokens:
            continue

        base_bin = os.path.basename(cmd_tokens[0])
        if base_bin in ('git', 'git-leandro'):
            # The next non-flag argument is the git subcommand
            sub_action = None
            for token in cmd_tokens[1:]:
                if token.startswith('-'):
                    continue
                # If command is 'git-leandro git commit', skip redundant 'git'
                if base_bin == 'git-leandro' and token == 'git':
                    continue
                sub_action = token
                break

            if sub_action:
                git_subcommands.append(sub_action)

    return git_subcommands


def main():
    try:
        raw_input = sys.stdin.read()
        if not raw_input.strip():
            print(json.dumps({"decision": "allow"}))
            return

        payload = json.loads(raw_input)
    except Exception:
        # In case of malformed input, allow with warning
        print(json.dumps({"decision": "allow"}))
        return

    tool_call = payload.get("toolCall", {})
    tool_name = tool_call.get("name", "")

    if tool_name != "run_command":
        print(json.dumps({"decision": "allow"}))
        return

    args = tool_call.get("args", {})
    command_line = args.get("CommandLine", "").strip()

    if not command_line:
        print(json.dumps({"decision": "allow"}))
        return

    git_subcommands = extract_git_subcommands(command_line)

    mutating_actions_found = [action for action in git_subcommands if action in MUTATING_GIT_COMMANDS]

    if not mutating_actions_found:
        # No mutating git actions found (read-only git or regular non-git command)
        print(json.dumps({"decision": "allow"}))
        return

    # A mutating git action was detected! Check authorization.
    transcript_path = payload.get("transcriptPath", "")

    has_auth_env = is_authorized_by_env_or_cmd(command_line)
    has_auth_transcript = is_authorized_by_transcript(transcript_path)

    if has_auth_env or has_auth_transcript:
        print(json.dumps({"decision": "allow"}))
        return

    # Unauthorized mutating git attempt -> HARD DENIAL
    forbidden_list = ", ".join(mutating_actions_found)
    reason = (
        f"[TRAVA DETERMINÍSTICA - SDK-GATE] Operação Git restrita detectada ('{forbidden_list}'). "
        f"Comandos de versionamento (add, commit, push) são EXCLUSIVOS do agente /git-committer. "
        f"O agente atual não possui autorização para versionar ou modificar o histórico do repositório. "
        f"Conclua a tarefa de implementação reportando os testes e aguarde a invocação do /git-committer."
    )

    response = {
        "decision": "deny",
        "reason": reason
    }
    print(json.dumps(response))


if __name__ == "__main__":
    main()
