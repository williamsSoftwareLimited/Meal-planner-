import React, { useEffect, useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const MEAL_SLOTS = ['breakfast', 'lunch', 'dinner', 'snacks'];
const DEFAULT_API_BASE_URL = 'http://127.0.0.1:8000';
const DEFAULT_SHOPPER_ID = 'default';

const createEmptyPlan = () =>
  DAYS.reduce((daysAcc, day) => {
    daysAcc[day] = MEAL_SLOTS.reduce((slotsAcc, slot) => {
      slotsAcc[slot] = '';
      return slotsAcc;
    }, {});
    return daysAcc;
  }, {});

const buildApiUrl = (apiBaseUrl, endpointPath, queryParams = {}) => {
  const normalizedBaseUrl = apiBaseUrl.endsWith('/') ? apiBaseUrl : `${apiBaseUrl}/`;
  const url = new URL(endpointPath.replace(/^\/+/, ''), normalizedBaseUrl);
  Object.entries(queryParams).forEach(([key, value]) => {
    url.searchParams.set(key, value);
  });
  return url.toString();
};

const getMealPlan = async (apiBaseUrl, shopperId) => {
  const endpoint = buildApiUrl(apiBaseUrl, 'backend/meal-plan.php', { shopperId });
  const response = await fetch(endpoint);
  if (!response.ok) {
    throw new Error('Failed to load meal plan');
  }

  const payload = await response.json();
  return payload.plan || createEmptyPlan();
};

const saveMealPlan = async (apiBaseUrl, shopperId, plan) => {
  const endpoint = buildApiUrl(apiBaseUrl, 'backend/meal-plan.php', { shopperId });
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ plan }),
  });

  if (!response.ok) {
    throw new Error('Failed to save meal plan');
  }
};

export default function WeeklyMealPlannerScreen({
  apiBaseUrl = DEFAULT_API_BASE_URL,
  shopperId = DEFAULT_SHOPPER_ID,
}) {
  const [plan, setPlan] = useState(createEmptyPlan);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let mounted = true;
    getMealPlan(apiBaseUrl, shopperId)
      .then((loadedPlan) => {
        if (mounted) {
          setPlan(loadedPlan);
        }
      })
      .catch(() => {
        Alert.alert('Error', 'Could not load meal plan');
      })
      .finally(() => {
        if (mounted) {
          setLoading(false);
        }
      });

    return () => {
      mounted = false;
    };
  }, [apiBaseUrl, shopperId]);

  const updateMeal = (day, slot, value) => {
    setPlan((current) => ({
      ...current,
      [day]: {
        ...current[day],
        [slot]: value,
      },
    }));
  };

  const onSave = async () => {
    try {
      setSaving(true);
      await saveMealPlan(apiBaseUrl, shopperId, plan);
      Alert.alert('Saved', 'Weekly meal plan saved');
    } catch {
      Alert.alert('Error', 'Could not save meal plan');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <Text>Loading meal plan...</Text>
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>Weekly Meal Planner</Text>
      {DAYS.map((day) => (
        <View key={day} style={styles.dayCard}>
          <Text style={styles.dayTitle}>{day}</Text>
          {MEAL_SLOTS.map((slot) => (
            <View key={`${day}-${slot}`} style={styles.inputWrap}>
              <Text style={styles.label}>{slot}</Text>
              <TextInput
                style={styles.input}
                placeholder={`Set ${slot} for ${day}`}
                value={plan[day]?.[slot] ?? ''}
                onChangeText={(value) => updateMeal(day, slot, value)}
              />
            </View>
          ))}
        </View>
      ))}

      <TouchableOpacity style={styles.button} onPress={onSave} disabled={saving}>
        <Text style={styles.buttonText}>{saving ? 'Saving...' : 'Save weekly plan'}</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: 16,
    paddingBottom: 32,
    backgroundColor: '#fff',
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    marginBottom: 12,
  },
  dayCard: {
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
  },
  dayTitle: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 8,
  },
  inputWrap: {
    marginBottom: 8,
  },
  label: {
    textTransform: 'capitalize',
    marginBottom: 4,
    color: '#333',
  },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 6,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  button: {
    marginTop: 10,
    backgroundColor: '#2563eb',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
  },
  buttonText: {
    color: '#fff',
    fontWeight: '600',
  },
});
