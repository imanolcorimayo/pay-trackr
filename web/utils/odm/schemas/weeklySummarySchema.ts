import { Schema } from '../schema';
import type { SchemaDefinition } from '../types';

export class WeeklySummarySchema extends Schema {
  protected collectionName = 'weeklySummaries';

  protected schema: SchemaDefinition = {
    userId: {
      type: 'string',
      required: true
    },
    stats: {
      type: 'object',
      required: true
    },
    aiInsight: {
      type: 'string',
      required: false
    },
    createdAt: {
      type: 'date',
      required: false
    }
  };
}
