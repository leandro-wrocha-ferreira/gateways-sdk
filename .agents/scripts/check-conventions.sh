#!/usr/bin/env bash

# Deterministic Conventions & Architectural Boundaries Validator
# gateways-sdk — PHP 8.2 Payment Gateways SDK
#
# Usage:
#   bash .agents/scripts/check-conventions.sh          # Checks all codebase files
#   bash .agents/scripts/check-conventions.sh --staged # Checks only staged git files
#
# Exit codes:
#   0 - All checks passed cleanly
#   1 - One or more rule violations detected

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

TOTAL_VIOLATIONS=0
CHECKED_FILES=0

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}   Deterministic Conventions & Architectural Validator (SDK)    ${NC}"
echo -e "${BLUE}================================================================${NC}"

# 1. Determine files to inspect
FILES=()
if [ "$1" == "--staged" ]; then
	echo -e "${YELLOW}Mode: Inspecting staged Git files only...${NC}\n"
	# Use git status / diff via git-leandro or git
	GIT_CMD="git"
	if command -v git-leandro &> /dev/null; then
		GIT_CMD="git-leandro"
	fi
	while IFS= read -r line; do
		[ -n "$line" ] && FILES+=("$line")
	done < <($GIT_CMD diff --name-only --cached --diff-filter=ACMR | grep -E '\.php$' || true)
else
	echo -e "${YELLOW}Mode: Inspecting all codebase PHP files...${NC}\n"
	while IFS= read -r line; do
		[ -n "$line" ] && FILES+=("$line")
	done < <(find src tests .agents/scripts -type f -name "*.php" 2>/dev/null | grep -v "vendor/" || true)
fi

if [ ${#FILES[@]} -eq 0 ]; then
	echo -e "${GREEN}No PHP files to inspect.${NC}"
	exit 0
fi

# 2. Iterate and validate each file
for file in "${FILES[@]}"; do
	if [ ! -f "$file" ]; then
		continue
	fi

	CHECKED_FILES=$((CHECKED_FILES + 1))
	FILE_VIOLATIONS=0

	# 1. Check Space Indentation (Tabs are mandatory per .editorconfig)
	SPACE_INDENT_MATCHES=$(grep -Hn -P '^[ ]{2,}' "$file" || true)
	if [ -n "$SPACE_INDENT_MATCHES" ]; then
		echo -e "\n${RED}[FALHA] Indentação por Espaços Detectada (obrigatório Tabs) em:${NC} $file"
		echo "$SPACE_INDENT_MATCHES" | head -n 5 | while read -r line; do
			echo -e "   ${YELLOW}Linha:${NC} $line"
		done
		FILE_VIOLATIONS=$((FILE_VIOLATIONS + 1))
	fi

	# 2. Check PSR-12 Bracket Style: Opening brace on same line as class/function declaration
	BRACE_MATCHES=$(grep -Hn -P '^\s*(final\s+|abstract\s+|readonly\s+)?(class|interface|trait|enum)\s+[a-zA-Z0-9_]+.*\{$' "$file" || true)
	METHOD_BRACE_MATCHES=$(grep -Hn -P '^\s*(public|protected|private)?\s*(static\s+)?function\s+[a-zA-Z0-9_]+\s*\([^)]*\)(\s*:\s*[a-zA-Z0-9_\\?|&]+)?\s*\{$' "$file" || true)
	if [ -n "$BRACE_MATCHES" ] || [ -n "$METHOD_BRACE_MATCHES" ]; then
		echo -e "\n${RED}[FALHA] Violação de Abertura de Chave PSR-12 (deve estar na próxima linha) em:${NC} $file"
		if [ -n "$BRACE_MATCHES" ]; then
			echo "$BRACE_MATCHES" | while read -r line; do
				echo -e "   ${YELLOW}Classe/Enum:${NC} $line"
			done
		fi
		if [ -n "$METHOD_BRACE_MATCHES" ]; then
			echo "$METHOD_BRACE_MATCHES" | while read -r line; do
				echo -e "   ${YELLOW}Método:${NC} $line"
			done
		fi
		FILE_VIOLATIONS=$((FILE_VIOLATIONS + 1))
	fi

	# 3. Check Vertical Alignment (prohibited: multiple spaces before => or =)
	ALIGN_MATCHES=$(grep -Hn -P '\s{2,}=>' "$file" || true)
	if [ -n "$ALIGN_MATCHES" ]; then
		echo -e "\n${RED}[FALHA] Alinhamento Vertical Detectado (múltiplos espaços antes de =>) em:${NC} $file"
		echo "$ALIGN_MATCHES" | while read -r line; do
			echo -e "   ${YELLOW}Linha:${NC} $line"
		done
		FILE_VIOLATIONS=$((FILE_VIOLATIONS + 1))
	fi

	# 4. Check Single-Letter Variables (prohibited per GEMINI.md)
	SINGLE_VAR_MATCHES=$(grep -Hn -P '\$(?!(this)\b)[a-zA-Z]\b(?!\w)' "$file" || true)
	if [ -n "$SINGLE_VAR_MATCHES" ]; then
		echo -e "\n${RED}[FALHA] Variável de Letra Única Detectada (proibido por GEMINI.md) em:${NC} $file"
		echo "$SINGLE_VAR_MATCHES" | while read -r line; do
			echo -e "   ${YELLOW}Linha:${NC} $line"
		done
		FILE_VIOLATIONS=$((FILE_VIOLATIONS + 1))
	fi

	# 5. Check declare(strict_types=1); in src/ and tests/
	if [[ "$file" == *"src/"* ]] || [[ "$file" == *"tests/"* ]]; then
		if ! grep -q "declare(strict_types=1);" "$file"; then
			echo -e "\n${RED}[FALHA] Ausência de declare(strict_types=1); em:${NC} $file"
			echo -e "   ${YELLOW}(Obrigatório no topo de todo arquivo PHP em src/ e tests/)${NC}"
			FILE_VIOLATIONS=$((FILE_VIOLATIONS + 1))
		fi
	fi

	# 6. Check Native/Generic Exceptions in Domain and Use Cases
	if [[ "$file" == *"src/Domain/"* ]] || [[ "$file" == *"src/Application/"* ]]; then
		if [[ "$file" != *"SdkException.php"* ]]; then
			NATIVE_EX_MATCHES=$(grep -Hn -P 'throw\s+new\s+\\?(RuntimeException|Exception|LogicException|BadMethodCallException|UnexpectedValueException)\b' "$file" || true)
			if [ -n "$NATIVE_EX_MATCHES" ]; then
				echo -e "\n${RED}[FALHA] Lançamento de Exceção Genérica Nativa em:${NC} $file"
				echo -e "   ${YELLOW}(Exceções devem herdar de SdkException ou ser semanticamente classificadas)${NC}"
				echo "$NATIVE_EX_MATCHES" | while read -r line; do
					echo -e "   ${YELLOW}Linha:${NC} $line"
				done
				FILE_VIOLATIONS=$((FILE_VIOLATIONS + 1))
			fi
		fi
	fi

	if [ "$FILE_VIOLATIONS" -gt 0 ]; then
		TOTAL_VIOLATIONS=$((TOTAL_VIOLATIONS + FILE_VIOLATIONS))
	fi
done

# Run structural checks when inspecting the whole project or staged
echo -e "\n${BLUE}================================================================${NC}"
echo -e "${BLUE}        Verificações Estruturais e de Integração Sistêmica        ${NC}"
echo -e "${BLUE}================================================================${NC}\n"

# Use Cases 1:1 Test Verifier
if [ -f ".agents/scripts/verify-usecase-tests.php" ]; then
	if ! php .agents/scripts/verify-usecase-tests.php; then
		TOTAL_VIOLATIONS=$((TOTAL_VIOLATIONS + 1))
	fi
fi

# Gateway Implementation Verifier
if [ -f ".agents/scripts/verify-gateway-contracts.php" ]; then
	if ! php .agents/scripts/verify-gateway-contracts.php; then
		TOTAL_VIOLATIONS=$((TOTAL_VIOLATIONS + 1))
	fi
fi

echo ""
echo -e "${BLUE}================================================================${NC}"
echo -e "Arquivos verificados: ${CHECKED_FILES}"

if [ "$TOTAL_VIOLATIONS" -eq 0 ]; then
	echo -e "${GREEN}✓ SUCESSO: Todos os arquivos cumprem as convenções do GEMINI.md!${NC}"
	echo -e "${BLUE}================================================================${NC}"
	exit 0
else
	echo -e "${RED}✗ ERRO: Foram encontradas ${TOTAL_VIOLATIONS} categorias de violação!${NC}"
	echo -e "${BLUE}================================================================${NC}"
	exit 1
fi
