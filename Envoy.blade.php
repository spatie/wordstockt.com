@setup
$branch = 'main';
$server = "wordstockt.com";
$server = "67.207.79.165";
$userAndServer = 'forge@'. $server;
$repository = "freekmurze/wordstockt.com";
$baseDir = "/home/forge/wordstockt.com";
$releasesDir = "{$baseDir}/releases";
$persistentDir = "{$baseDir}/persistent";
$currentDir = "{$baseDir}/current";
$newReleaseName = date('Ymd-His');
$newReleaseDir = "{$releasesDir}/{$newReleaseName}";
$user = get_current_user();

function logMessage($message) {
return "echo '\033[32m" .$message. "\033[0m';\n";
}
@endsetup

@servers(['local' => '127.0.0.1', 'remote' => $userAndServer])

@macro('deploy')
startDeployment
cloneRepository
runComposer
generateAssets
updateSymlinks
optimizeInstallation
backupDatabase
migrateDatabase
blessNewRelease
cleanOldReleases
finishDeploy
@endmacro

@macro('deploy-code')
deployOnlyCode
@endmacro

@task('startDeployment', ['on' => 'local'])
{{ logMessage("🏃  Starting deployment…") }}
git checkout {{ $branch }}
git pull origin {{ $branch }}
@endtask

@task('cloneRepository', ['on' => 'remote'])
{{ logMessage("🌀  Cloning repository…") }}
[ -d {{ $releasesDir }} ] || mkdir {{ $releasesDir }};
[ -d {{ $persistentDir }} ] || mkdir {{ $persistentDir }};
[ -d {{ $persistentDir }}/storage ] || mkdir {{ $persistentDir }}/storage;

[ -d {{ $persistentDir }}/storage/app ] || mkdir {{ $persistentDir }}/storage/app;
[ -d {{ $persistentDir }}/storage/framework ] || mkdir {{ $persistentDir }}/storage/framework;
[ -d {{ $persistentDir }}/storage/logs ] || mkdir {{ $persistentDir }}/storage/logs;

[ -d {{ $persistentDir }}/storage/framework/cache ] || mkdir {{ $persistentDir }}/storage/framework/cache;
[ -d {{ $persistentDir }}/storage/framework/cache/data ] || mkdir {{ $persistentDir }}/storage/framework/cache/data;
[ -d {{ $persistentDir }}/storage/framework/sessions ] || mkdir {{ $persistentDir }}/storage/framework/sessions;
[ -d {{ $persistentDir }}/storage/framework/views ] || mkdir {{ $persistentDir }}/storage/framework/views;

cd {{ $releasesDir }};

# Create the release dir
mkdir {{ $newReleaseDir }};

# Clone the repo
git clone --depth 1 git@github.com:{{ $repository }} --branch {{ $branch }} {{ $newReleaseName }}

# Configure sparse checkout
cd {{ $newReleaseDir }}
git config core.sparsecheckout true
echo "*" > .git/info/sparse-checkout
echo "!storage" >> .git/info/sparse-checkout
echo "!public/build" >> .git/info/sparse-checkout
git read-tree -mu HEAD

# Mark release
cd {{ $newReleaseDir }}
echo "{{ $newReleaseName }}" > public/release-name.txt
@endtask

@task('runComposer', ['on' => 'remote'])

# Import the environment config
cd {{ $newReleaseDir }};
ln -nfs {{ $baseDir }}/.env .env;

cd {{ $newReleaseDir }};
{{ logMessage("🚚  Running Composer…") }}
ln -nfs {{ $baseDir }}/auth.json auth.json;
composer install --prefer-dist --no-scripts --no-dev -q -o;
@endtask

@task('generateAssets', ['on' => 'remote'])
{{ logMessage("🌅  Generating assets…") }}
cd {{ $newReleaseDir }};
npm ci --audit false
npm run build
rm -rf node_modules
@endtask

@task('updateSymlinks', ['on' => 'remote'])
{{ logMessage("🔗  Updating symlinks to persistent data…") }}
# Remove the storage directory and replace with persistent data
rm -rf {{ $newReleaseDir }}/storage;
cd {{ $newReleaseDir }};
ln -nfs {{ $baseDir }}/persistent/storage storage;
@endtask

@task('optimizeInstallation', ['on' => 'remote'])
{{ logMessage("✨  Optimizing installation…") }}
cd {{ $newReleaseDir }};
php artisan clear-compiled;
@endtask

@task('backupDatabase', ['on' => 'remote'])
{{ logMessage("📀  Backing up database…") }}
cd {{ $newReleaseDir }}
php artisan backup:run
@endtask

@task('migrateDatabase', ['on' => 'remote'])
{{ logMessage("🙈  Migrating database…") }}
cd {{ $newReleaseDir }};
php artisan migrate --force;
@endtask

@task('blessNewRelease', ['on' => 'remote'])
{{ logMessage("🙏  Blessing new release…") }}
ln -nfs {{ $newReleaseDir }} {{ $currentDir }};
cd {{ $newReleaseDir }}
php artisan horizon:terminate
php artisan reverb:restart
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan event:cache

sudo service php8.4-fpm restart
sudo supervisorctl restart all
@endtask

@task('cleanOldReleases', ['on' => 'remote'])
{{ logMessage("🚾  Cleaning up old releases…") }}
# Delete all but the 3 most recent.
cd {{ $releasesDir }}
ls -dt {{ $releasesDir }}/* | tail -n +4 | xargs -d "\n" sudo chown -R forge .;
ls -dt {{ $releasesDir }}/* | tail -n +4 | xargs -d "\n" rm -rf;
@endtask

@task('finishDeploy', ['on' => 'local'])
{{ logMessage("🚀  Application deployed!") }}
@endtask

@task('deployOnlyCode',['on' => 'remote'])
{{ logMessage("💻  Deploying code changes…") }}
cd {{ $currentDir }}
git pull origin {{ $branch }}
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan event:cache
php artisan horizon:terminate
php artisan reverb:restart
sudo service php8.4-fpm restart
@endtask
