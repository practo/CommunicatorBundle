from fabric.api import lcd, local, settings, task, puts, hide
from fabric.colors import green
import os

ROOT_DIR = os.path.abspath(os.path.dirname(__file__))


def info(text):
    puts(green(text))


@task
def polish():
    with lcd(ROOT_DIR):
        with settings(hide('running'), warn_only=True):
            # Fix file permissions
            info('Fixing file permissions...')
            local('find . '
                  '-path ./.git -prune -o '  # Exclude .git directory
                  '-name *.sh -exec chmod 0755 {} \\; -o '
                  '-type d -exec chmod 0755 {} \\; -o '
                  '-type f -exec chmod 0644 {} \\;')

            # Fixing whitespace
            info('Fixing trailing whitespace...')
            local("find . "
                  "-path ./.git -prune -o "  # Exclude .git directory
                  "-type f "
                  "-exec sed -i 's/\\s\\+$//' {} \\;")

            # Converting tabs to spaces
            info('Converting tabs to spaces...')
            local("find . "
                  "-path ./.git -prune -o "  # Exclude .git directory
                  "-type f "
                  "-exec sed -i 's/\\t/    /' {} \\;")

            # Find merge conflict leftovers
            info('Finding merge conflict leftovers...')
            local('! find . '
                  '-path ./.git -prune -o '
                  '-type d -o '
                  '-print0 | '
                  'xargs -0 grep -Pn "^(<|=|>){7}(?![<=>])"')

            # Run coding standards check
            info('Running coding standards check...')
            local('phpcs --standard=Symfony2 . --ignore=genphp')

            # Check for var_dump, echo or die statements
            info('Checking for var_dump, echo or die statements...')
            local('! find . -name "*.php" -print0 | '
                  'xargs -0 egrep -n "var_dump|echo|die" | grep -v "NOCHECK"')
