import os
import sys

from functools import wraps

from fabric.api import lcd, local, settings, task, puts, hide
from fabric.colors import green

ROOT_DIR = os.path.abspath(os.path.dirname(__file__))


def info(text):
    puts(green(text))

_sniffs = {
    'critical': [],
    'major': [],
    'minor': [],
    'info': [],
}


def sniff(*args, **kwargs):
    """ Decorator to collect sniffs and execute on polish
        :param severity: Keyword argument only.
                         One of 'critical', 'major', 'minor', 'info'
                         Default: 'critical'
        :type severity: str
    """
    DEFAULT_SEVERITY = 'critical'
    invoked = bool(not args or kwargs)
    severity = kwargs.get('severity', DEFAULT_SEVERITY)

    def decorator(func):
        @task
        @wraps(func)
        def wrapper(*args, **kwargs):
            with lcd(ROOT_DIR), settings(hide('running')):
                return func(*args, **kwargs)
        _sniffs[severity].append(wrapper)
        return wrapper
    return decorator if invoked else decorator(args[0])


@task
def polish(all=False):
    results = list()
    with settings(warn_only=True):
        if all:
            sniffs_to_run = sum((_sniffs[severity] for severity in [
                'critical',
                'major',
                'minor',
                'info'
            ]), [])
        else:
            sniffs_to_run = _sniffs['critical']
        for sniff in sniffs_to_run:
            results.append(sniff())

    if any(result.failed for result in results):
        sys.exit(1)


__all__ = [
    'sniff',
    'info',
    'local',
    'polish'
]
