# Dynamic Content Translation Implementation Guide

## Overview

This guide explains how to implement dynamic content translation for the Vue.js frontend using the updated Laravel backend APIs. The system supports Swedish (default) and English languages with automatic DeepL translation for dynamic content.

## API Language Support

### Language Parameter Options

The APIs now support language specification through:

1. **Query Parameter**: `?lang=en` or `?lang=sv`
2. **HTTP Header**: `Accept-Language: en` or `Accept-Language: sv`
3. **Default**: Swedish (`sv`) if no language is specified

### Supported Endpoints

All API endpoints now support language parameters:

- `GET /api/questions/work?lang=en`
- `GET /api/questions/school?lang=en`
- `GET /api/questions/life?lang=en`
- `GET /api/profile/{id}/values?lang=en`
- `POST /api/profile/{id}/value?lang=en`

## Vue.js Frontend Implementation

### 1. Language State Management

```javascript
// store/language.js
export const useLanguageStore = defineStore('language', {
  state: () => ({
    currentLanguage: 'sv', // Default to Swedish
    availableLanguages: ['sv', 'en']
  }),
  
  actions: {
    setLanguage(lang) {
      if (this.availableLanguages.includes(lang)) {
        this.currentLanguage = lang;
        // Store in localStorage for persistence
        localStorage.setItem('preferred-language', lang);
      }
    },
    
    initLanguage() {
      // Load from localStorage or use browser default
      const saved = localStorage.getItem('preferred-language');
      const browserLang = navigator.language.startsWith('en') ? 'en' : 'sv';
      this.currentLanguage = saved || browserLang;
    }
  }
});
```

### 2. API Service with Language Support

```javascript
// services/api.js
import axios from 'axios';

class ApiService {
  constructor() {
    this.baseURL = process.env.VUE_APP_API_URL || 'http://127.0.0.1:8000/api';
    this.setupAxios();
  }

  setupAxios() {
    axios.defaults.baseURL = this.baseURL;
    
    // Add language interceptor
    axios.interceptors.request.use(config => {
      const languageStore = useLanguageStore();
      
      // Add language as query parameter
      if (config.params) {
        config.params.lang = languageStore.currentLanguage;
      } else {
        config.params = { lang: languageStore.currentLanguage };
      }
      
      // Add language header
      config.headers['Accept-Language'] = languageStore.currentLanguage;
      
      return config;
    });
  }

  // Questions API
  async getWorkQuestions() {
    const response = await axios.get('/questions/work');
    return response.data;
  }

  async getSchoolQuestions() {
    const response = await axios.get('/questions/school');
    return response.data;
  }

  async getLifeQuestions() {
    const response = await axios.get('/questions/life');
    return response.data;
  }

  // Profile API
  async getProfileValues(profileId) {
    const response = await axios.get(`/profile/${profileId}/values`);
    return response.data;
  }

  async updateProfileValue(profileId, data) {
    const response = await axios.post(`/profile/${profileId}/value`, data);
    return response.data;
  }

  async getGoals(profileId) {
    const response = await axios.get(`/profile/${profileId}/goals`);
    return response.data;
  }

  async updateGoals(profileId, goals) {
    const response = await axios.post(`/profile/${profileId}/goals`, { goals });
    return response.data;
  }

  async getPlannedGoals(profileId, finished = false) {
    const response = await axios.get(`/profile/${profileId}/plan`, {
      params: { finished }
    });
    return response.data;
  }

  async updatePlannedGoals(profileId, plan) {
    const response = await axios.post(`/profile/${profileId}/plan`, { plan });
    return response.data;
  }
}

export default new ApiService();
```

### 3. Language Switcher Component

```vue
<!-- components/LanguageSwitcher.vue -->
<template>
  <div class="language-switcher">
    <button 
      @click="switchLanguage('sv')" 
      :class="{ active: currentLanguage === 'sv' }"
      class="lang-btn"
    >
      <img src="/images/se.png" alt="Svenska" />
      SV
    </button>
    
    <button 
      @click="switchLanguage('en')" 
      :class="{ active: currentLanguage === 'en' }"
      class="lang-btn"
    >
      <img src="/images/gb.png" alt="English" />
      EN
    </button>
  </div>
</template>

<script>
import { useLanguageStore } from '@/store/language';

export default {
  name: 'LanguageSwitcher',
  setup() {
    const languageStore = useLanguageStore();
    
    const switchLanguage = (lang) => {
      languageStore.setLanguage(lang);
      // Reload current page data with new language
      window.location.reload();
    };
    
    return {
      currentLanguage: computed(() => languageStore.currentLanguage),
      switchLanguage
    };
  }
};
</script>

<style scoped>
.language-switcher {
  display: flex;
  gap: 10px;
}

.lang-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}

.lang-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.lang-btn img {
  width: 20px;
  height: 15px;
}
</style>
```

### 4. Questions Component with Translation

```vue
<!-- components/Questions.vue -->
<template>
  <div class="questions-container">
    <div v-if="loading" class="loading">
      {{ $t('common.loading') }}
    </div>
    
    <div v-else>
      <!-- Page Label -->
      <h2>{{ questions.pageLabel }}</h2>
      
      <!-- Groups -->
      <div v-for="group in questions.groups" :key="group.label" class="question-group">
        <h3>{{ group.label }}</h3>
        
        <!-- Questions -->
        <div v-for="question in group.questions" :key="question.id" class="question">
          <div class="question-description">{{ question.description }}</div>
          
          <!-- Question Options -->
          <div v-if="question.data && question.data.length" class="question-options">
            <label v-for="option in question.data" :key="option.value" class="option">
              <input 
                :type="getInputType(question.type)" 
                :name="question.name" 
                :value="option.value"
                v-model="answers[question.name]"
              />
              <span>{{ option.label }}</span>
            </label>
          </div>
          
          <!-- Help Text -->
          <div v-if="question.help_text" class="help-text">
            {{ question.help_text }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useLanguageStore } from '@/store/language';
import apiService from '@/services/api';

export default {
  name: 'Questions',
  props: {
    questionType: {
      type: String,
      required: true,
      validator: value => ['work', 'school', 'life'].includes(value)
    }
  },
  
  setup(props) {
    const languageStore = useLanguageStore();
    const questions = ref({});
    const loading = ref(true);
    const answers = ref({});
    
    const loadQuestions = async () => {
      try {
        loading.value = true;
        
        let response;
        switch (props.questionType) {
          case 'work':
            response = await apiService.getWorkQuestions();
            break;
          case 'school':
            response = await apiService.getSchoolQuestions();
            break;
          case 'life':
            response = await apiService.getLifeQuestions();
            break;
        }
        
        questions.value = response;
      } catch (error) {
        console.error('Error loading questions:', error);
      } finally {
        loading.value = false;
      }
    };
    
    const getInputType = (type) => {
      switch (type) {
        case 'radio':
          return 'radio';
        case 'checkbox':
          return 'checkbox';
        case 'text':
          return 'text';
        default:
          return 'radio';
      }
    };
    
    onMounted(() => {
      loadQuestions();
    });
    
    // Watch for language changes
    watch(() => languageStore.currentLanguage, () => {
      loadQuestions();
    });
    
    return {
      questions,
      loading,
      answers,
      getInputType
    };
  }
};
</script>
```

### 5. Profile Component with Translation

```vue
<!-- components/Profile.vue -->
<template>
  <div class="profile-container">
    <div v-if="loading" class="loading">
      {{ $t('common.loading') }}
    </div>
    
    <div v-else>
      <!-- Profile Values -->
      <div class="profile-values">
        <h3>{{ $t('profile.values') }}</h3>
        <div v-for="value in profile.values" :key="value.name" class="value-item">
          <span class="value-name">{{ value.name }}:</span>
          <span class="value-data">{{ value.value }}</span>
        </div>
      </div>
      
      <!-- Profile Factors -->
      <div class="profile-factors">
        <h3>{{ $t('profile.factors') }}</h3>
        <div v-for="factor in profile.factors" :key="factor.category_id" class="factor-item">
          <span class="factor-label">{{ factor.label }}</span>
          <span class="factor-value">{{ factor.value }}</span>
          <span class="factor-status">{{ factor.status_text }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useLanguageStore } from '@/store/language';
import apiService from '@/services/api';

export default {
  name: 'Profile',
  props: {
    profileId: {
      type: [String, Number],
      required: true
    }
  },
  
  setup(props) {
    const languageStore = useLanguageStore();
    const profile = ref({});
    const loading = ref(true);
    
    const loadProfile = async () => {
      try {
        loading.value = true;
        
        const [valuesResponse, factorsResponse] = await Promise.all([
          apiService.getProfileValues(props.profileId),
          apiService.getGoals(props.profileId)
        ]);
        
        profile.value = {
          values: valuesResponse,
          factors: factorsResponse.goals
        };
      } catch (error) {
        console.error('Error loading profile:', error);
      } finally {
        loading.value = false;
      }
    };
    
    onMounted(() => {
      loadProfile();
    });
    
    // Watch for language changes
    watch(() => languageStore.currentLanguage, () => {
      loadProfile();
    });
    
    return {
      profile,
      loading
    };
  }
};
</script>
```

### 6. Main App Component

```vue
<!-- App.vue -->
<template>
  <div id="app">
    <!-- Header with Language Switcher -->
    <header class="app-header">
      <div class="header-content">
        <h1>{{ $t('app.title') }}</h1>
        <LanguageSwitcher />
      </div>
    </header>
    
    <!-- Main Content -->
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<script>
import { onMounted } from 'vue';
import { useLanguageStore } from '@/store/language';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

export default {
  name: 'App',
  components: {
    LanguageSwitcher
  },
  
  setup() {
    const languageStore = useLanguageStore();
    
    onMounted(() => {
      // Initialize language on app start
      languageStore.initLanguage();
    });
  }
};
</script>
```

## Translation Strategy

### 1. Static Content
- Use Vue i18n for static UI text
- Store translations in `locales/sv.json` and `locales/en.json`

### 2. Dynamic Content
- All dynamic content comes from Laravel APIs
- APIs automatically handle translation based on language parameter
- DeepL translation is applied server-side for missing English content

### 3. Caching Strategy
- Laravel caches DeepL translations for 7 days
- Frontend can cache API responses based on language
- Clear cache when content is updated

## Testing

### API Testing
```bash
# Test Swedish content
curl "http://127.0.0.1:8000/api/questions/life?lang=sv"

# Test English content
curl "http://127.0.0.1:8000/api/questions/life?lang=en"

# Test with header
curl -H "Accept-Language: en" "http://127.0.0.1:8000/api/questions/life"
```

### Frontend Testing
1. Switch language using the language switcher
2. Verify all content updates immediately
3. Check that language preference persists across sessions
4. Test with different question types (work, school, life)

## Performance Considerations

1. **Caching**: DeepL translations are cached for 7 days
2. **Lazy Loading**: Load questions only when needed
3. **Optimization**: Use computed properties for expensive operations
4. **Error Handling**: Graceful fallback to Swedish if translation fails

## Deployment Notes

1. Ensure DeepL API key is set in production environment
2. Configure proper cache settings for production
3. Set up monitoring for translation API usage
4. Consider implementing translation quality checks

This implementation provides a robust, scalable solution for dynamic content translation that works seamlessly with your existing Laravel backend and Vue.js frontend. 