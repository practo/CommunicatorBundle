# CommunicatorBundle

Symfony2 bundle (`practo/communicator-bundle`) that queues mail, SMS, WhatsApp,
and push-notification jobs for the Practo Communicator service. It is a library
consumed by other applications — it has no runnable entrypoint of its own.

## Commands

```bash
composer install                 # install PHP deps (composer.lock is gitignored)
pip install -r requirements.txt  # install fab-polish, the lint harness
fab polish                       # run every sniff in fabfile.py
phpcs --standard=Symfony2 . --ignore=genphp,vendor   # coding-standard check alone
php -l <file>                    # syntax-check a single file
```

There is no test suite and no CI lane in this repository; `fab polish` is the
only automated gate. It is also mutating — the whitespace, tab, and permission
sniffs rewrite files in place rather than just reporting.

Consumers install the bundle from the git VCS repository and register
`Practo\CommunicatorBundle\PractoCommunicatorBundle` in `AppKernel.php`. See
`README.md` for the required `parameters.yml` keys.

## Boundaries

- `Clients/Types.php` is Thrift-generated (`praccomm` namespace, Thrift 0.9.0).
  Do not hand-edit it; regenerate from the upstream Thrift IDL instead. Adding a
  new mail/SMS type means adding a constant *and* its `$__names` entry.
- `composer.json` pins `"target-dir": "Practo/CommunicatorBundle"` with PSR-0
  autoloading. Class names, namespaces, and directory layout must stay in
  lockstep — renaming a directory breaks every consumer's autoloader.
- Every client is wired by hand in `Resources/config/services.yml`. A new client
  class needs three edits there: a `%...class%` parameter, a `services:` entry
  with its constructor arguments, and the `calls: [setRequest, ['@request_stack']]`
  line if it extends `Clients/Base.php`.
- Client `sendX()` signatures are public API for downstream apps. Callers pass
  long positional argument lists, so appending optional parameters is safe but
  reordering or removing any existing parameter is a breaking change.
- Queue names are declared as constants on `Queue/AbstractQueue.php` and mirrored
  as parameters in `services.yml`. The consuming worker reads the same names —
  changing a constant's string value silently orphans in-flight jobs.
- `Queue/QueueFactory.php` chooses a transport by parsing
  `communicator.queue.uri`. Only `beanstalk://` and `https?://sqs.*.amazonaws.com`
  are supported; anything else throws.
- Credentials (`communicator.api.secret`, `communicator.s3.bucket`) come from the
  consuming app's `parameters.yml`. Never commit a real secret or bucket name.

## Gotchas

- The `check_var_dump` sniff fails the build on any occurrence of `var_dump`,
  `echo`, or `die` in a `.php` file. If a line legitimately needs one, append a
  `NOCHECK` comment on that line — that is the only escape hatch.
- Tabs and trailing whitespace are auto-converted to four spaces by `fab polish`.
  Match the surrounding indentation or the sniff will rewrite your diff.
- `Clients/Base.php::getCommunicatorUri()` and
  `Queue/AbstractQueue.php::getQueueName()` derive the target host and queue
  suffix from the *current request's* hostname, splitting the subdomain on `-`
  (e.g. `app-staging.practo.com` → `communicator-staging`, queue `…-staging`).
  In CLI or worker contexts there is no request, so callers must call
  `setDomain()` explicitly or these will fault on a null host.
- `DependencyInjection/Configuration.php` defines an empty config tree. The
  bundle is configured entirely through container *parameters*, not through a
  `practo_communicator:` block in `config.yml`.
- `CommunicatorClient`/`CommunicatorClientV2` authenticate with a
  `sha256(secret . nonce)` token in the `X-AUTH-TOKEN` header alongside a `nonce`
  header. Both are hand-rolled cURL wrappers that `throw \Exception` on non-200,
  so wrap calls in try/catch rather than checking a return value.
- There are no release tags. Downstream projects pin a commit SHA in
  `composer.lock`, so any master commit is immediately consumable — keep master
  releasable.
- The bundle targets PHP >= 5.4 and Symfony >= 2.0. Avoid PHP 7+ syntax (scalar
  type hints, return types, null coalescing, short closures).
- `pda/pheanstalk` is pinned to `<=2.1`. Newer versions rename the package's
  class prefix; see the README FAQ for the `pheanstalk_pheanstalk` error.
