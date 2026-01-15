// Weather API functionality
async function fetchWeather(city) {
  try {
    // Show loading state
    document.getElementById('weatherTemp').textContent = 'Loading...';
    document.getElementById('weatherCondition').textContent = 'Loading...';
    document.getElementById('weatherHumidity').textContent = '--';
    document.getElementById('weatherWind').textContent = '--';

    const response = await fetch(`../user/api_weather.php?city=${encodeURIComponent(city)}`);
    const result = await response.json();

    if (!result.success) {
      console.error('Weather API error:', result.message || 'Unknown error');
      // Show fallback
      document.getElementById('weatherTemp').textContent = '--°C';
      document.getElementById('weatherCondition').textContent = 'Weather unavailable';
      return;
    }

    // Update current weather
    const current = result.data.current;
    document.getElementById('weatherLocation').textContent = current.location || city;
    document.getElementById('weatherTemp').textContent = current.temp || '--°C';
    document.getElementById('weatherCondition').textContent = current.condition || '--';

    document.getElementById('weatherHumidity').textContent =
      current.humidity ? current.humidity.replace('%', '') : '--';

    document.getElementById('weatherWind').textContent =
      current.wind ? current.wind.replace(' km/h', '') : '--';

    const iconImg = document.querySelector('#weatherIcon img');
    if (current.icon) {
      iconImg.src = current.icon;
      iconImg.alt = current.condition || 'Weather Icon';
    }

    // Update forecast
    const forecastContainer = document.getElementById('weatherForecast');
    forecastContainer.innerHTML = '';

    if (result.data.forecast && result.data.forecast.length > 0) {
      result.data.forecast.forEach(day => {
        const el = document.createElement('div');
        el.className = 'text-center small forecast-day';

        // Handle undefined temps
        const tempMax = day.temp_max !== undefined ? day.temp_max : '--';
        const tempMin = day.temp_min !== undefined ? day.temp_min : '--';

        el.innerHTML = `
          <div class="fw-semibold">${day.day || ''}</div>
          <img src="${day.icon || ''}" width="40" alt="${day.condition || ''}">
          <div>${tempMax}° / ${tempMin}°</div>
        `;
        forecastContainer.appendChild(el);
      });
    }

  } catch (err) {
    console.error('Weather JS error:', err);
    // Show error state
    document.getElementById('weatherTemp').textContent = '--°C';
    document.getElementById('weatherCondition').textContent = 'Connection error';
    document.getElementById('weatherHumidity').textContent = '--';
    document.getElementById('weatherWind').textContent = '--';
    document.getElementById('weatherForecast').innerHTML = '';
  }
}

// Auto-load weather on page load
document.addEventListener('DOMContentLoaded', () => {
  // currentLocation is defined in your dashboard.php JS
  if (typeof currentLocation !== 'undefined') {
    fetchWeather(currentLocation);
  }
});
