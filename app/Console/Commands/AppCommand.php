<?php

namespace App\Console\Commands;

use App\Actions\UploadToPlayStoreAction;
use App\Domain\Support\Notifications\TestNotification;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class AppCommand extends Command
{
    protected $signature = 'app';

    protected $description = 'Interactive helper for running, building, and updating the mobile app';

    protected string $appPath = '../wordstockt-app';

    public function handle(): int
    {
        $action = select(
            label: 'What do you want to do?',
            options: [
                'run' => 'Run dev server',
                'build-ios' => 'Build iOS (local → TestFlight)',
                'build-android' => 'Build Android (local → Play Store)',
                // 'build-eas' => 'Build via EAS (cloud)',
                'notification' => 'Send test notification',
                'update' => 'Push OTA update',
            ],
        );

        return match ($action) {
            'run' => $this->runDev(),
            'update' => $this->pushUpdate(),
            'build-ios' => $this->buildIos(),
            'build-android' => $this->buildAndroid(),
            // 'build-eas' => $this->buildEas(),
            'notification' => $this->sendTestNotification(),
            default => self::FAILURE,
        };
    }

    protected function runDev(): int
    {
        $platform = select(
            label: 'Platform?',
            options: [
                'ios' => 'iOS Simulator',
                'android' => 'Android Emulator',
                'web' => 'Web Browser',
            ],
        );

        if ($platform === 'ios') {
            $this->bootIosSimulator();
        }

        if ($platform === 'android') {
            $this->disableNewArchForAndroid();
        }

        $api = select(
            label: 'API?',
            options: [
                'local' => 'Local (wordstockt.com.test)',
                'production' => 'Production (wordstockt.com)',
            ],
        );

        $this->killPortProcess(8081);
        $this->ensureDevBuildInstalled($platform);

        $this->setProductionEnv($api === 'production');

        return $this->runInApp("npx expo start --{$platform} --clear");
    }

    protected function bootIosSimulator(): void
    {
        $device = select(
            label: 'Device?',
            options: [
                'iphone' => 'iPhone',
                'ipad' => 'iPad',
            ],
        ) === 'ipad' ? 'iPad Pro 13-inch (M5)' : 'iPhone 17 Pro';

        info("Booting {$device}...");
        Process::run("xcrun simctl boot \"{$device}\" 2>/dev/null");
    }

    protected function ensureDevBuildInstalled(string $platform): void
    {
        if ($platform === 'web') {
            return;
        }

        $checkCommand = match ($platform) {
            'ios' => 'xcrun simctl listapps booted 2>/dev/null | grep -q com.wordstockt.app && echo yes || echo no',
            'android' => 'adb shell pm list packages 2>/dev/null | grep -q com.wordstockt.app && echo yes || echo no',
            default => 'echo no',
        };

        $installed = trim(Process::run($checkCommand)->output()) === 'yes';

        if (! $installed) {
            info('Dev build not installed. Building...');
            $this->runInApp("npx expo run:{$platform}");
        }
    }

    protected function pushUpdate(): int
    {
        $channel = select(
            label: 'Channel?',
            options: [
                'development' => 'Development',
                'production' => 'Production',
            ],
        );

        return $this->runInApp("eas update --channel {$channel}");
    }

    protected function buildEas(): int
    {
        $profile = select(
            label: 'Profile?',
            options: [
                'development' => 'Development',
                'production' => 'Production',
            ],
        );

        $platform = select(
            label: 'Platform?',
            options: [
                'ios' => 'iOS',
                'android' => 'Android',
                'all' => 'Both',
            ],
        );

        $submit = confirm('Submit to stores after build?', default: false);

        $command = "eas build --profile {$profile} --platform {$platform}";
        if ($submit) {
            $command .= ' --auto-submit';
        }

        return $this->runInApp($command);
    }

    protected function buildIos(): int
    {
        $clean = $this->selectBuildType();
        $buildMethod = select(
            label: 'Build method?',
            options: [
                'terminal' => 'Terminal (upload via Transporter)',
                'xcode' => 'Xcode (manual)',
            ],
        );

        $appPath = $this->getAppPath();

        $this->incrementBuildNumber('ios');

        if (! $this->runPrebuild('ios', $clean)) {
            return self::FAILURE;
        }

        $this->patchIosForProduction($appPath);

        info('iOS prebuild completed!');

        return $buildMethod === 'terminal'
            ? $this->buildIosViaTerminal($appPath)
            : $this->buildIosViaXcode($appPath);
    }

    protected function buildIosViaTerminal(string $appPath): int
    {
        $archivePath = $appPath.'/build/WordStockt.xcarchive';
        $exportPath = $appPath.'/build';

        $this->ensureDirectoryExists($appPath.'/build');

        if (! $this->confirmAppleAccountSetup()) {
            return self::FAILURE;
        }

        info('Archiving...');

        $archiveResult = Process::path($appPath)
            ->timeout(600)
            ->run([
                'xcodebuild',
                '-workspace', 'ios/WordStockt.xcworkspace',
                '-scheme', 'WordStockt',
                '-configuration', 'Release',
                '-destination', 'generic/platform=iOS',
                '-archivePath', $archivePath,
                'archive',
                '-allowProvisioningUpdates',
                'DEVELOPMENT_TEAM=97KRXCRMAY',
                'CODE_SIGN_STYLE=Automatic',
                'ONLY_ACTIVE_ARCH=NO',
                '-quiet',
            ], function (string $type, string $output): void {
                $this->output->write($output);
            });

        if (! $archiveResult->successful()) {
            error('Archive failed!');
            $this->output->writeln($archiveResult->errorOutput());

            return self::FAILURE;
        }

        info('Exporting...');

        $exportOptionsPlist = $this->createExportOptionsPlist($appPath);

        $exportResult = Process::path($appPath)
            ->timeout(300)
            ->run([
                'xcodebuild',
                '-exportArchive',
                '-archivePath', $archivePath,
                '-exportPath', $exportPath,
                '-exportOptionsPlist', $exportOptionsPlist,
            ], function (string $type, string $output): void {
                $this->output->write($output);
            });

        unlink($exportOptionsPlist);

        if (! $exportResult->successful()) {
            error('Export failed!');

            return self::FAILURE;
        }

        $ipaPath = $exportPath.'/WordStockt.ipa';

        if (! file_exists($ipaPath)) {
            error("IPA not found: {$ipaPath}");

            return self::FAILURE;
        }

        info('Build complete!');
        note("IPA: {$ipaPath}");

        $this->handleIosUpload($appPath);

        return self::SUCCESS;
    }

    protected function confirmAppleAccountSetup(): bool
    {
        return true;
    }

    protected function createExportOptionsPlist(string $appPath): string
    {
        $path = $appPath.'/ExportOptions.plist';

        file_put_contents($path, <<<'PLIST'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>method</key>
    <string>app-store-connect</string>
    <key>teamID</key>
    <string>97KRXCRMAY</string>
    <key>uploadBitcode</key>
    <false/>
    <key>uploadSymbols</key>
    <true/>
    <key>manageAppVersionAndBuildNumber</key>
    <true/>
</dict>
</plist>
PLIST);

        return $path;
    }

    protected function handleIosUpload(string $appPath): void
    {
        $action = select(
            label: 'Upload method?',
            options: [
                'transporter' => 'Open Transporter',
                'folder' => 'Open build folder',
                'skip' => 'Skip',
            ],
        );

        match ($action) {
            'transporter' => Process::run('open -a Transporter'),
            'folder' => Process::run("open {$appPath}/build"),
            default => null,
        };

        if ($action === 'transporter' && confirm('Open build folder?', default: true)) {
            Process::run("open {$appPath}/build");
        }
    }

    protected function buildIosViaXcode(string $appPath): int
    {
        $xcodePath = realpath($appPath.'/ios/WordStockt.xcworkspace');

        note(<<<'INSTRUCTIONS'
        Next steps:

        1. In Xcode, verify signing (Team: Spatie, automatic)
        2. Select "Any iOS Device (arm64)"
        3. Product → Archive
        4. Distribute App → App Store Connect → Upload
        5. Wait for processing in App Store Connect (~15 min)
        INSTRUCTIONS);

        if (confirm('Open Xcode?', default: true)) {
            Process::run("open {$xcodePath}");
        }

        return self::SUCCESS;
    }

    protected function patchIosForProduction(string $appPath): void
    {
        $podfileProps = $appPath.'/ios/Podfile.properties.json';

        if (file_exists($podfileProps)) {
            $props = json_decode(file_get_contents($podfileProps), true);
            unset($props['EX_DEV_CLIENT_NETWORK_INSPECTOR']);
            file_put_contents($podfileProps, json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }

        $this->disableSentryUploads('ios', $appPath);
    }

    protected function buildAndroid(): int
    {
        $clean = $this->selectBuildType();
        $appPath = $this->getAppPath();

        $this->incrementBuildNumber('android');
        $this->enableNewArchForAndroid();

        if (! $this->runPrebuild('android', $clean)) {
            return self::FAILURE;
        }

        $this->patchAndroidForProduction($appPath);

        info('Running Gradle build...');

        $result = Process::path($appPath.'/android')
            ->timeout(600)
            ->run('./gradlew bundleRelease', function (string $type, string $output): void {
                $this->output->write($output);
            });

        if (! $result->successful()) {
            error('Gradle build failed!');

            return self::FAILURE;
        }

        $aabPath = $appPath.'/android/app/build/outputs/bundle/release/app-release.aab';

        if (! file_exists($aabPath)) {
            error("AAB not found: {$aabPath}");

            return self::FAILURE;
        }

        info('Build complete!');
        info("AAB: {$aabPath}");

        $this->handleAndroidUpload($appPath, $aabPath);

        return self::SUCCESS;
    }

    protected function patchAndroidForProduction(string $appPath): void
    {
        $this->disableSentryUploads('android', $appPath);
        $this->configureGradleMemory($appPath);
        $this->configureAndroidSigning($appPath);
    }

    protected function enableNewArchForAndroid(): void
    {
        $appJsonPath = $this->getAppPath().'/app.json';
        $appJson = json_decode(file_get_contents($appJsonPath), true);

        if (($appJson['expo']['newArchEnabled'] ?? false) === false) {
            $appJson['expo']['newArchEnabled'] = true;
            file_put_contents($appJsonPath, json_encode($appJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            info('Enabled new architecture (required by reanimated)');
        }
    }

    protected function disableSentryUploads(string $platform, string $appPath): void
    {
        if ($platform === 'ios') {
            file_put_contents(
                $appPath.'/ios/sentry.properties',
                "defaults.url=https://sentry.io/\ndefaults.org=spatie2\ndefaults.project=wordstockt-app\ncli.enabled=false\n"
            );

            $xcodeEnvLocal = $appPath.'/ios/.xcode.env.local';
            $xcodeEnv = file_get_contents($xcodeEnvLocal);

            if (! str_contains($xcodeEnv, 'SENTRY_DISABLE_AUTO_UPLOAD')) {
                file_put_contents($xcodeEnvLocal, $xcodeEnv."export SENTRY_DISABLE_AUTO_UPLOAD=true\n");
            }

            return;
        }

        $buildGradle = $appPath.'/android/app/build.gradle';
        $content = file_get_contents($buildGradle);
        $content = str_replace(
            "apply from: new File([\"node\", \"--print\", \"require('path').dirname(require.resolve('@sentry/react-native/package.json'))\"].execute().text.trim(), \"sentry.gradle\")",
            "// Sentry disabled for local builds\n// apply from: new File([\"node\", \"--print\", \"require('path').dirname(require.resolve('@sentry/react-native/package.json'))\"].execute().text.trim(), \"sentry.gradle\")",
            $content
        );
        file_put_contents($buildGradle, $content);
    }

    protected function configureGradleMemory(string $appPath): void
    {
        $gradleProps = $appPath.'/android/gradle.properties';
        $content = file_get_contents($gradleProps);

        $content = preg_replace(
            '/org\.gradle\.jvmargs=.+/',
            'org.gradle.jvmargs=-Xmx4096m -XX:MaxMetaspaceSize=1024m -XX:+HeapDumpOnOutOfMemoryError',
            $content
        );

        if (! str_contains($content, 'org.gradle.java.home')) {
            $content .= "\norg.gradle.java.home=/Applications/Android Studio.app/Contents/jbr/Contents/Home\n";
        }

        file_put_contents($gradleProps, $content);
    }

    protected function configureAndroidSigning(string $appPath): void
    {
        $keystoreSource = $appPath.'/credentials/android/keystore.properties';

        if (! file_exists($keystoreSource)) {
            return;
        }

        $envVars = $this->loadEnvFile($appPath.'/.env');

        $keystoreContent = file_get_contents($keystoreSource);
        $keystoreContent = preg_replace_callback(
            '/\$\{(\w+)\}/',
            fn ($matches) => $envVars[$matches[1]] ?? $matches[0],
            $keystoreContent
        );
        file_put_contents($appPath.'/android/keystore.properties', $keystoreContent);

        $this->patchBuildGradleForSigning($appPath.'/android/app/build.gradle');

        info('Configured release signing');
    }

    protected function patchBuildGradleForSigning(string $buildGradlePath): void
    {
        $content = file_get_contents($buildGradlePath);

        $keystoreSetup = <<<'GRADLE'

// Load keystore properties for release signing
def keystorePropertiesFile = rootProject.file("keystore.properties")
def keystoreProperties = new Properties()
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

GRADLE;

        $content = preg_replace('/(android \{)/', "$1\n$keystoreSetup", $content, 1);

        $releaseConfig = <<<'GRADLE'
        release {
            if (keystorePropertiesFile.exists()) {
                storeFile file("../../credentials/android/" + keystoreProperties['storeFile'])
                storePassword keystoreProperties['storePassword']
                keyAlias keystoreProperties['keyAlias']
                keyPassword keystoreProperties['keyPassword']
            }
        }
GRADLE;

        $content = preg_replace(
            '/(signingConfigs \{[\s\S]*?debug \{[\s\S]*?\})\s*(\})/',
            "$1\n$releaseConfig\n    $2",
            $content,
            1
        );

        $content = preg_replace(
            '/buildTypes \{\s*debug \{\s*signingConfig signingConfigs\.\w+\s*\}\s*release \{[^}]*signingConfig signingConfigs\.\w+/s',
            "buildTypes {\n        debug {\n            signingConfig signingConfigs.debug\n        }\n        release {\n            signingConfig signingConfigs.release",
            $content,
            1
        );

        file_put_contents($buildGradlePath, $content);
    }

    protected function handleAndroidUpload(string $appPath, string $aabPath): void
    {
        $playStoreKeyFile = $appPath.'/credentials/android/play-store-key.json';

        if (file_exists($playStoreKeyFile) && confirm('Upload to Play Store?', default: true)) {
            $this->uploadToPlayStore($aabPath, $playStoreKeyFile);

            return;
        }

        if (! file_exists($playStoreKeyFile)) {
            note('To enable auto-upload, add credentials/android/play-store-key.json');
        }

        note('Manual upload: https://play.google.com/console');

        if (confirm('Open build folder?')) {
            Process::run('open '.dirname($aabPath));
        }
    }

    protected function uploadToPlayStore(string $aabPath, string $keyFile): void
    {
        info('Uploading to Play Store...');

        try {
            $result = app(UploadToPlayStoreAction::class)->execute(
                aabPath: $aabPath,
                serviceAccountKeyPath: $keyFile,
                track: 'internal',
            );

            info("Uploaded! Version code: {$result['version_code']}");
            info('View: https://play.google.com/console');
        } catch (\Exception $e) {
            error("Upload failed: {$e->getMessage()}");
            info('Manual upload: https://play.google.com/console');

            if (confirm('Open build folder?')) {
                Process::run('open '.dirname($aabPath));
            }
        }
    }

    protected function sendTestNotification(): int
    {
        $userId = search(
            label: 'Search for a user',
            options: fn (string $value) => $value !== ''
                ? User::query()
                    ->withCount('pushTokens')
                    ->where(fn ($q) => $q
                        ->where('username', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                    )
                    ->get()
                    ->mapWithKeys(fn ($user): array => [
                        $user->id => $user->username.($user->push_tokens_count > 0 ? " ({$user->push_tokens_count} devices)" : ' (no token)'),
                    ])
                    ->all()
                : [],
            placeholder: 'Type to search...'
        );

        $user = User::find($userId);

        if (! $user) {
            error('User not found');

            return self::FAILURE;
        }

        if ($user->pushTokens()->count() === 0) {
            error("No push tokens for {$user->username}");

            return self::FAILURE;
        }

        info("Sending to {$user->username}...");
        $user->notify(new TestNotification);
        info('Sent!');

        return self::SUCCESS;
    }

    protected function selectBuildType(): bool
    {
        return select(
            label: 'Build type?',
            options: [
                'clean' => 'Clean (after native changes)',
                'normal' => 'Normal (faster)',
            ],
        ) === 'clean';
    }

    protected function incrementBuildNumber(string $platform): void
    {
        $appJsonPath = $this->getAppPath().'/app.json';
        $appJson = json_decode(file_get_contents($appJsonPath), true);

        $key = $platform === 'ios' ? 'buildNumber' : 'versionCode';
        $current = $platform === 'ios'
            ? (int) ($appJson['expo']['ios'][$key] ?? 1)
            : ($appJson['expo']['android'][$key] ?? 1);

        $new = $current + 1;

        $appJson['expo'][$platform][$key] = $platform === 'ios' ? (string) $new : $new;

        file_put_contents($appJsonPath, json_encode($appJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        info("Build number: {$current} → {$new}");
    }

    protected function runPrebuild(string $platform, bool $clean): bool
    {
        $command = $clean
            ? "npx expo prebuild --clean --platform {$platform}"
            : "npx expo prebuild --platform {$platform}";

        info($clean ? 'Prebuild (clean)...' : 'Prebuild...');

        $result = Process::path($this->getAppPath())
            ->timeout(600)
            ->run($command, function (string $type, string $output): void {
                $this->output->write($output);
            });

        if (! $result->successful()) {
            error('Prebuild failed!');

            return false;
        }

        return true;
    }

    protected function getAppPath(): string
    {
        return base_path($this->appPath);
    }

    protected function runInApp(string $command): int
    {
        passthru("cd {$this->appPath} && {$command}", $exitCode);

        return $exitCode;
    }

    protected function killPortProcess(int $port): void
    {
        Process::run("lsof -ti:{$port} | xargs kill -9 2>/dev/null");
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function loadEnvFile(string $path): array
    {
        $vars = [];

        if (! file_exists($path)) {
            return $vars;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value);
        }

        return $vars;
    }

    protected function disableNewArchForAndroid(): void
    {
        $appJsonPath = $this->getAppPath().'/app.json';
        $appJson = json_decode(file_get_contents($appJsonPath), true);

        if (($appJson['expo']['newArchEnabled'] ?? false) === true) {
            $appJson['expo']['newArchEnabled'] = false;
            file_put_contents($appJsonPath, json_encode($appJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            info('Disabled new architecture (react-native-screens Android bug)');

            info('Uninstalling old build (architecture changed)...');
            Process::run('adb uninstall com.wordstockt.app 2>/dev/null');
        }
    }

    protected function setProductionEnv(bool $useProduction): void
    {
        $envLocalPath = $this->getAppPath().'/.env.local';

        if ($useProduction) {
            file_put_contents($envLocalPath, "EXPO_PUBLIC_USE_PRODUCTION=true\n");
            info('Using production API (wordstockt.com)');
        } else {
            if (file_exists($envLocalPath)) {
                unlink($envLocalPath);
            }
            info('Using local API (wordstockt.com.test)');
        }
    }
}
