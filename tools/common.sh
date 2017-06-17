#!/bin/bash

print_error() {
	printf "\e[41m%s\e[0m\n" "$*"
}

print_warning() {
	printf "\e[43;30m%s\e[0m\n" "$*"
}

print_cmd() {
	#printf "\e[46;30;1m%s\e[0m\n" "$*"
	printf "\e[36;1m%s\e[0m\n" "$*"
}

print_message() {
	printf "\e[36m%s\e[0m\n" "$*"
}

die() {
	print_error "$@"
	exit 1
}

exec_cmd() {
	print_cmd "> $@"
	"$@" || die "failed; cwd=$(pwd)"
}

_pushd() {
	pushd "$*" >/dev/null 2>&1 || die "pushd $* failed"
}

_popd() {
	popd >/dev/null 2>&1 || die "popd failed"
}
