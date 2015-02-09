import sys

from fabric.api import local, settings, env

from fabpolish import sniff, info, polish


@sniff(severity='minor')
def fix_file_permissions():
    info('Fixing file permissions...')
    return local(
        '! find `git ls-files` '
        '-name "*.sh" -o '  # Exclude shell scripts
        '-not -perm 0664 -print0 | '
        'tee /dev/stderr | '
        'xargs -0 chmod 0664 >/dev/null 2>&1'
    )


@sniff(severity='major')
def fix_script_permissions():
    info('Fixing script permissions...')
    return local(
        '! find `git ls-files` '
        '-name "*.sh" '
        '-not -perm 0775 -print0 | '
        'tee /dev/stderr | '
        'xargs -0 chmod 0775 >/dev/null 2>&1'
    )


@sniff(severity='minor')
def fix_trailing_whitespace():
    info('Fixing trailing whitespace...')
    return local(
        "! git ls-files -z | "
        "xargs -0 grep -PlZn '(\\s+$)|(\\t)' | "
        "tee /dev/stderr | "
        "xargs -0 sed -i -e 's/\\s\\+$//' -e 's/\\t/    /g' "
        ">/dev/null 2>&1"
    )


@sniff(severity='critical')
def find_merge_conflict_leftovers():
    info('Finding merge conflict leftovers...')
    return local("! git grep -P '^(<|=|>){7}(?![<=>])'")


@sniff(severity='critical')
def check_php_debug_stmts():
    info('Checking for var_dump, echo or die statements...')
    return local(
        "! git ls-files -z | "
        "grep -PZz '.*\.(php|phtml)$' | "
        "xargs -0 grep -P '(var_dump|echo|die)'"
    )


@sniff(severity='minor')
def fix_psr2_violations():
    info('Running coding standards check...')
    with settings(warn_only=True):
        result = local(
            "git ls-files -z | "
            "grep -vZz '/genphp/' | "
            "grep -vZz '/Thrift\.php$' | "
            "grep -PZz '.*\.(php|phtml)$' | "
            "xargs -0 phpcs --standard=PSR2"
        )
    if result.failed:
        local(
            "git ls-files -z | "
            "grep -vZz '/genphp/' | "
            "grep -PZz '.*\.(php|phtml)$' | "
            "xargs -0 phpcbf --standard=PSR2"
        )
    if not env.warn_only and result.failed:
        sys.exit(1)
    return result


if __name__ == "__main__":
    polish()
