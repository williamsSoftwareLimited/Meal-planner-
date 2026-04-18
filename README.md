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
- `POST /backend/meal-plan.php`
  - Body:
    ```json
    {
      "shopperId": "default",
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

Update `API_BASE_URL` in `mobile/WeeklyMealPlannerScreen.js` to your PHP host.
