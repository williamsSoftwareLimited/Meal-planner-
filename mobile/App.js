/**
 * Meal Planner – React Native app entry point.
 *
 * This file wires up the navigation stack and mounts the
 * WeeklyMealPlannerScreen for the currently signed-in shopper.
 *
 * Usage
 * -----
 * 1. Install deps:  npm install
 * 2. Run on device: npx react-native run-android   (or run-ios)
 *
 * Environment
 * -----------
 * Create a `.env` file (or configure via your CI/CD secrets) with:
 *
 *   API_BASE_URL=https://your-backend.example.com
 *   SHOPPER_ID=shopper-123
 *
 * For local development the defaults in WeeklyMealPlannerScreen already
 * point to http://127.0.0.1:8000 with shopperId "default".
 */

import React from 'react';
import { SafeAreaView, StatusBar, StyleSheet } from 'react-native';
import WeeklyMealPlannerScreen from './WeeklyMealPlannerScreen';

// Replace these values with your own API URL and shopper identifier.
// In a real app you would read them from environment variables or an
// auth context (e.g., after the user signs in).
const API_BASE_URL = 'http://127.0.0.1:8000';
const SHOPPER_ID = 'default';

export default function App() {
  return (
    <SafeAreaView style={styles.root}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      <WeeklyMealPlannerScreen
        apiBaseUrl={API_BASE_URL}
        shopperId={SHOPPER_ID}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: '#fff',
  },
});
