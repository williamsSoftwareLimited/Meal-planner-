# Meal-planner-

Minimal weekly meal planner starter for:
- React Native shopper UI
- PHP backend API

## Use case covered

A shopper can set meals from Monday to Sunday for each meal slot:
- breakfast
- lunch
- dinner
- snacks

Example:
- Monday breakfast: yoghurt, milk
- Monday dinner: salmon
- Tuesday dinner: stew

## Backend (PHP)

File: `backend/meal-plan.php`

### Endpoints

- `GET /backend/meal-plan.php?shopperId=default`
  - Returns the saved weekly plan for a shopper (or an empty template if not saved yet).
- `POST /backend/meal-plan.php?shopperId=default`
  - Body:
    ```json
    {
      "plan": {
        "Monday": {
          "breakfast": "yoghurt, milk",
          "lunch": "",
          "dinner": "salmon",
          "snacks": ""
        }
      }
    }
    ```

### Run locally

```bash
php -S 127.0.0.1:8000 -t .
```

## Mobile (React Native)

File: `mobile/WeeklyMealPlannerScreen.js`

The screen:
- loads an existing weekly plan from the PHP API
- allows editing meal values for each day and meal slot
- saves the whole Monday-Sunday plan back to the API

Configure via props:
- `apiBaseUrl` (default: `http://127.0.0.1:8000`)
- `shopperId` (default: `default`)

For browser-based clients, optionally set `MEAL_PLANNER_ALLOWED_ORIGINS` to a comma-separated allowlist of trusted origins.

## Integration example

File: `mobile/App.js`

`App.js` is the minimal React Native entry point that wires up `WeeklyMealPlannerScreen`:

```jsx
import React from 'react';
import { SafeAreaView, StatusBar, StyleSheet } from 'react-native';
import WeeklyMealPlannerScreen from './WeeklyMealPlannerScreen';

const API_BASE_URL = 'http://127.0.0.1:8000'; // swap for your backend URL
const SHOPPER_ID = 'default';               // swap for your auth user ID

export default function App() {
  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: '#fff' }}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      <WeeklyMealPlannerScreen
        apiBaseUrl={API_BASE_URL}
        shopperId={SHOPPER_ID}
      />
    </SafeAreaView>
  );
}
```

Register `App` as the root component in `index.js`:

```js
import { AppRegistry } from 'react-native';
import App from './mobile/App';
import { name as appName } from './app.json';

AppRegistry.registerComponent(appName, () => App);
```
