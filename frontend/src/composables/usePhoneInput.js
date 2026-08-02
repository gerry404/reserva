import { ref, computed, onMounted } from 'vue'

const countries = [
  { code: 'CM', name: 'Cameroun', dial: '+237', flag: '🇨🇲' },
  { code: 'CI', name: "Côte d'Ivoire", dial: '+225', flag: '🇨🇮' },
  { code: 'SN', name: 'Sénégal', dial: '+221', flag: '🇸🇳' },
  { code: 'GA', name: 'Gabon', dial: '+241', flag: '🇬🇦' },
  { code: 'CG', name: 'Congo', dial: '+242', flag: '🇨🇬' },
  { code: 'CD', name: 'RD Congo', dial: '+243', flag: '🇨🇩' },
  { code: 'BF', name: 'Burkina Faso', dial: '+226', flag: '🇧🇫' },
  { code: 'ML', name: 'Mali', dial: '+223', flag: '🇲🇱' },
  { code: 'GN', name: 'Guinée', dial: '+224', flag: '🇬🇳' },
  { code: 'BJ', name: 'Bénin', dial: '+229', flag: '🇧🇯' },
  { code: 'TG', name: 'Togo', dial: '+228', flag: '🇹🇬' },
  { code: 'NE', name: 'Niger', dial: '+227', flag: '🇳🇪' },
  { code: 'TD', name: 'Tchad', dial: '+235', flag: '🇹🇩' },
  { code: 'CF', name: 'Centrafrique', dial: '+236', flag: '🇨🇫' },
  { code: 'GQ', name: 'Guinée équatoriale', dial: '+240', flag: '🇬🇶' },
  { code: 'NG', name: 'Nigeria', dial: '+234', flag: '🇳🇬' },
  { code: 'GH', name: 'Ghana', dial: '+233', flag: '🇬🇭' },
  { code: 'KE', name: 'Kenya', dial: '+254', flag: '🇰🇪' },
  { code: 'TZ', name: 'Tanzanie', dial: '+255', flag: '🇹🇿' },
  { code: 'UG', name: 'Ouganda', dial: '+256', flag: '🇺🇬' },
  { code: 'RW', name: 'Rwanda', dial: '+250', flag: '🇷🇼' },
  { code: 'ET', name: 'Éthiopie', dial: '+251', flag: '🇪🇹' },
  { code: 'ZA', name: 'Afrique du Sud', dial: '+27', flag: '🇿🇦' },
  { code: 'MA', name: 'Maroc', dial: '+212', flag: '🇲🇦' },
  { code: 'DZ', name: 'Algérie', dial: '+213', flag: '🇩🇿' },
  { code: 'TN', name: 'Tunisie', dial: '+216', flag: '🇹🇳' },
  { code: 'EG', name: 'Égypte', dial: '+20', flag: '🇪🇬' },
  { code: 'MG', name: 'Madagascar', dial: '+261', flag: '🇲🇬' },
  { code: 'MU', name: 'Maurice', dial: '+230', flag: '🇲🇺' },
  { code: 'FR', name: 'France', dial: '+33', flag: '🇫🇷' },
  { code: 'BE', name: 'Belgique', dial: '+32', flag: '🇧🇪' },
  { code: 'CH', name: 'Suisse', dial: '+41', flag: '🇨🇭' },
  { code: 'CA', name: 'Canada', dial: '+1', flag: '🇨🇦' },
  { code: 'US', name: 'États-Unis', dial: '+1', flag: '🇺🇸' },
  { code: 'GB', name: 'Royaume-Uni', dial: '+44', flag: '🇬🇧' },
  { code: 'DE', name: 'Allemagne', dial: '+49', flag: '🇩🇪' },
  { code: 'ES', name: 'Espagne', dial: '+34', flag: '🇪🇸' },
  { code: 'IT', name: 'Italie', dial: '+39', flag: '🇮🇹' },
  { code: 'PT', name: 'Portugal', dial: '+351', flag: '🇵🇹' },
  { code: 'NL', name: 'Pays-Bas', dial: '+31', flag: '🇳🇱' },
  { code: 'SE', name: 'Suède', dial: '+46', flag: '🇸🇪' },
  { code: 'NO', name: 'Norvège', dial: '+47', flag: '🇳🇴' },
  { code: 'DK', name: 'Danemark', dial: '+45', flag: '🇩🇰' },
  { code: 'FI', name: 'Finlande', dial: '+358', flag: '🇫🇮' },
  { code: 'PL', name: 'Pologne', dial: '+48', flag: '🇵🇱' },
  { code: 'AT', name: 'Autriche', dial: '+43', flag: '🇦🇹' },
  { code: 'IE', name: 'Irlande', dial: '+353', flag: '🇮🇪' },
  { code: 'TR', name: 'Turquie', dial: '+90', flag: '🇹🇷' },
  { code: 'RU', name: 'Russie', dial: '+7', flag: '🇷🇺' },
  { code: 'IN', name: 'Inde', dial: '+91', flag: '🇮🇳' },
  { code: 'CN', name: 'Chine', dial: '+86', flag: '🇨🇳' },
  { code: 'JP', name: 'Japon', dial: '+81', flag: '🇯🇵' },
  { code: 'KR', name: 'Corée du Sud', dial: '+82', flag: '🇰🇷' },
  { code: 'AU', name: 'Australie', dial: '+61', flag: '🇦🇺' },
  { code: 'NZ', name: 'Nouvelle-Zélande', dial: '+64', flag: '🇳🇿' },
  { code: 'BR', name: 'Brésil', dial: '+55', flag: '🇧🇷' },
  { code: 'MX', name: 'Mexique', dial: '+52', flag: '🇲🇽' },
  { code: 'AR', name: 'Argentine', dial: '+54', flag: '🇦🇷' },
  { code: 'CO', name: 'Colombie', dial: '+57', flag: '🇨🇴' },
  { code: 'CL', name: 'Chili', dial: '+56', flag: '🇨🇱' },
  { code: 'PE', name: 'Pérou', dial: '+51', flag: '🇵🇪' },
  { code: 'AE', name: 'Émirats arabes unis', dial: '+971', flag: '🇦🇪' },
  { code: 'SA', name: 'Arabie saoudite', dial: '+966', flag: '🇸🇦' },
  { code: 'LB', name: 'Liban', dial: '+961', flag: '🇱🇧' },
  { code: 'HT', name: 'Haïti', dial: '+509', flag: '🇭🇹' },
]

/**
 * The same country list, for screens that need to pick a country without a
 * phone input attached (onboarding, business settings).
 */
export const COUNTRIES = countries

export function usePhoneInput() {
  const selectedCountry = ref(countries[0]) // Default: Cameroun
  const phoneNumber = ref('')
  const dropdownOpen = ref(false)
  const searchQuery = ref('')
  const detected = ref(false)

  const filteredCountries = computed(() => {
    if (!searchQuery.value) return countries
    const q = searchQuery.value.toLowerCase()
    return countries.filter(c =>
      c.name.toLowerCase().includes(q) ||
      c.dial.includes(q) ||
      c.code.toLowerCase().includes(q)
    )
  })

  const fullPhone = computed(() => {
    const num = phoneNumber.value.replace(/\s/g, '')
    if (!num) return ''
    return `${selectedCountry.value.dial} ${num}`
  })

  function selectCountry(country) {
    selectedCountry.value = country
    dropdownOpen.value = false
    searchQuery.value = ''
  }

  // Auto-detect country via IP
  async function detectCountry() {
    try {
      const res = await fetch('https://ipapi.co/json/', { signal: AbortSignal.timeout(3000) })
      const data = await res.json()
      if (data.country_code) {
        const found = countries.find(c => c.code === data.country_code)
        if (found) {
          selectedCountry.value = found
          detected.value = true
        }
      }
    } catch {
      // Silently fail - keep default
    }
  }

  onMounted(() => {
    detectCountry()
  })

  return {
    countries,
    selectedCountry,
    phoneNumber,
    dropdownOpen,
    searchQuery,
    filteredCountries,
    fullPhone,
    selectCountry,
  }
}
