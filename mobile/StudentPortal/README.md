# Student Portal — Android WebView APK

Wraps the live student portal:
https://schoolerp.softflipsolutions.com/portal/index.php

## Features
- Full-screen WebView login + dashboard
- Pull to refresh
- Back button goes to previous page (then exit confirm)
- Offline screen with Retry
- Downloads open in system browser / downloads app
- External links open outside the app

## Build APK (Android Studio)

1. Install [Android Studio](https://developer.android.com/studio)
2. **File → Open** → select folder:
   `SchoolERP/mobile/StudentPortal`
3. Wait for Gradle sync
4. **Build → Build Bundle(s) / APK(s) → Build APK(s)**
5. APK path:
   `app/build/outputs/apk/debug/app-debug.apk`

### Release / installable signed APK
**Build → Generate Signed Bundle / APK → APK**

## Change portal URL
Edit `app/src/main/res/values/strings.xml`:
```xml
<string name="portal_url">https://schoolerp.softflipsolutions.com/portal/index.php</string>
```

## Package
- App ID: `com.softflip.schoolerp.portal`
- Min Android: 7.0 (API 24)
