# Personal Payment Tracking & Financial Management System

A comprehensive Progressive Web Application designed for personal financial management, focusing on recurring payment tracking, one-time expense management, and financial analytics with automated payment instance generation and status tracking.

## Technical Stack

- **Frontend**: Nuxt 3 (Vue 3) with TypeScript
- **Styling**: Tailwind CSS with custom design system
- **Database & Backend**: Firebase (Firestore, Auth, Hosting)
- **State Management**: Pinia with VueFire integration
- **PWA**: Service Worker with offline capabilities
- **Icons**: unplugin-icons with multiple icon sets (Mdi, Lucide, etc.)
- **Dates**: DayJS library (not native Date object)
- **Charts**: Chart.js for financial analytics
- **Notifications**: Vue3-Toastify for user feedback

## Core Architectural Principles

### 1. Dual Payment System Architecture
- **Axiom**: Two distinct payment types with unified interface
- **Recurrent Payments**: Template-based with automatic monthly instance generation
- **One-time Payments**: Direct payment records for current month
- Both types integrate seamlessly in summary and analytics views

### 2. Payment Instance Management
- **Axiom**: Recurrent payments generate individual instances per month
- Each instance tracks payment status independently
- Historical payment data maintained across months
- 6-month rolling view for comprehensive tracking

### 3. Real-time State Synchronization
- **Axiom**: All data changes sync immediately with Firebase
- Pinia stores cache data locally for performance
- VueFire provides reactive database bindings
- Optimistic updates for immediate UI feedback

### 4. Mobile-First Progressive Web App
- **Axiom**: Responsive design with mobile-first approach
- Installable PWA with offline capabilities
- Touch-friendly interface with large interaction targets
- Adaptive layouts (table view desktop, card view mobile)

### 5. Type-Safe Development Pattern
- **Axiom**: TypeScript throughout with comprehensive interface definitions
- Strongly typed Firebase operations
- Type-safe state management with Pinia
- Interface-driven component contracts

## Authentication & User Management

### Login Flow
- Entry point: Landing page with Google OAuth
- Firebase Authentication handles user sessions
- Auth middleware protects payment-related routes
- Post-login redirect to `/recurrent` dashboard

### User Context
- Single-user application (no multi-tenant architecture)
- User ID (`userId`) attached to all payment records
- Google profile information stored in user context
- Session management handled by Firebase Auth

## Project Structure

### Core Stores (Pinia)
- `payment.ts` - One-time payment management
- `recurrent.ts` - Recurring payment templates and instances
- `index.ts` - Legacy store (being phased out)

### Page Structure
- `/` - Landing page with authentication
- `/recurrent/` - Main dashboard for recurring payments
- `/payments/` - One-time payments management
- `/summary/` - Financial analytics and reports

### Key Components
- `payments/` - One-time payment components
- `recurrents/` - Recurring payment components
- `ui/` - Shared UI components (modals, filters, loaders)

### Utilities & Configuration
- `interfaces/` - TypeScript type definitions
- `composables/` - Vue composition utilities
- `utils/` - Common utility functions
- `scripts/` - Database migration scripts

## Database Schema (Firestore)

### Core Collections

#### payment2 (One-time Payments)
```
payment2/{document-id}/
  id: string                     // Document ID
  title: string                  // Payment description
  description: string            // Additional details
  amount: number                 // Payment amount
  category: string               // Payment category
  isPaid: boolean                // Payment status
  paidDate: Timestamp|null       // When payment was made
  recurrentId: string|null       // Link to recurrent template (if applicable)
  paymentType: string            // "one-time" or "recurrent"
  userId: string                 // User reference
  createdAt: Timestamp           // Creation timestamp
  dueDate: Timestamp|null        // When payment is due
```

#### recurrent (Recurring Payment Templates)
```
recurrent/{document-id}/
  id: string                     // Document ID
  title: string                  // Payment name
  description: string            // Payment description
  amount: number                 // Payment amount
  startDate: string              // When recurring payments begin
  dueDateDay: string             // Day of month payment is due
  endDate: string|null           // When recurring payments end (null = indefinite)
  timePeriod: string             // Frequency ("monthly", "yearly", etc.)
  category: string               // Payment category
  isCreditCard: boolean          // Credit card payment flag
  creditCardId: string|null      // Credit card reference
  userId: string                 // User reference
  createdAt: Timestamp           // Creation timestamp
```

### Legacy Collections (Migration Support)
- `payment` - Original payment structure (v1)
- `tracker` - Monthly payment tracking (deprecated)

## Payment Categories System

### Standard Categories
- **Housing**: Rent, mortgage, utilities
- **Transportation**: Car payments, fuel, transit
- **Food & Dining**: Groceries, restaurants
- **Entertainment**: Streaming, gaming, activities
- **Healthcare**: Insurance, medical expenses
- **Shopping**: Clothing, personal items
- **Finance**: Loans, credit cards, investments
- **Education**: Courses, subscriptions, books

### Category Features
- Color-coded visual system
- Consistent across all payment types
- Used for analytics and filtering
- Customizable per user preference

## Key Features & Functionality

### Recurring Payment Management
- **Template Creation**: Define payment schedules and amounts
- **Instance Generation**: Automatic monthly payment creation
- **Status Tracking**: Individual payment status per month
- **6-Month View**: Rolling timeline of past and future payments
- **Bulk Operations**: Mark multiple payments as paid

### One-Time Payment Tracking
- **Current Month Focus**: Shows current month payments only
- **Quick Entry**: Fast payment creation and editing
- **Status Management**: Simple paid/unpaid toggling
- **Category Organization**: Visual grouping by payment type

### Financial Analytics & Summary
- **Interactive Charts**: Line charts for trends, pie charts for category distribution
- **Multi-Month Comparison**: Spending analysis across different periods
- **Key Metrics**: Total spend, average monthly cost, completion rates
- **Category Insights**: Detailed breakdown by spending category
- **Export Capabilities**: Data export for external analysis

### Progressive Web App Features
- **Offline Access**: Core functionality works without internet
- **App Installation**: Installable on mobile and desktop
- **Push Notifications**: Payment reminders and alerts
- **Fast Loading**: Optimized performance with caching

## Data Migration & Legacy Support

### Migration Strategy
- **Scripts Directory**: Automated migration tools
- **Version Compatibility**: Maintains support for legacy data
- **Progressive Migration**: Users can migrate at their own pace
- **Data Integrity**: Validation and backup during migration

### Database Evolution
- **V1 to V2 Migration**: From `payment` to `payment2` collection
- **Schema Improvements**: Better type safety and data structure
- **Performance Optimizations**: Indexed queries and efficient data access

## Development Guidelines

### Payment System Patterns
1. **Always distinguish between templates and instances**
2. **Use proper TypeScript interfaces for all data structures**
3. **Maintain real-time sync with Firebase**
4. **Implement optimistic updates for better UX**
5. **Handle offline scenarios gracefully**

### Component Development
1. **Follow composition API patterns**
2. **Use Pinia stores for state management**
3. **Implement proper loading states**
4. **Add toast notifications for user feedback**
5. **Ensure mobile responsiveness**

### Data Management
1. **Validate data before Firebase operations**
2. **Use proper error handling and recovery**
3. **Implement proper TypeScript types**
4. **Cache frequently accessed data locally**
5. **Handle user authentication state changes**
