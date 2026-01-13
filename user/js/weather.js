// Weather API functionality
async function fetchWeather() {
  try {
    // Show loading state
    document.getElementById('weatherTemp').textContent = 'Loading...';
    document.getElementById('weatherCondition').textContent = 'Loading...';

    const response = await fetch(`api_weather.php?city=${encodeURIComponent(currentLocation)}`);
    const result = await response.json();

    if (result.success) {
      updateCurrentWeather(result.data);
      updateWeatherForecast(result.data);
      console.log('Weather loaded for:', currentLocation);
    } else {
      console.error('Weather API error:', result.error);
      // Show fallback message
      document.getElementById('weatherTemp').textContent = '--°C';
      document.getElementById('weatherCondition').textContent = 'Weather unavailable';
      document.getElementById('weatherHumidity').textContent = '--';
      document.getElementById('weatherWind').textContent = '--';
    }
  } catch (error) {
    console.error('Error fetching weather:', error);
    // Show error state
    document.getElementById('weatherTemp').textContent = '--°C';
    document.getElementById('weatherCondition').textContent = 'Connection error';
    document.getElementById('weatherHumidity').textContent = '--';
    document.getElementById('weatherWind').textContent = '--';
  }
}

function updateCurrentWeather(data) {
  document.getElementById('weatherTemp').textContent = data.current.temp;
  document.getElementById('weatherCondition').textContent = data.current.condition;
  document.getElementById('weatherHumidity').textContent = data.current.humidity;
  document.getElementById('weatherWind').textContent = data.current.wind;

  const weatherIcon = document.querySelector('#weatherIcon img');
  weatherIcon.src = data.current.icon;
  if (data.current.filter) {
    weatherIcon.style.filter = data.current.filter;
  }
}

function updateWeatherForecast(data) {
  const forecastContainer = document.getElementById('weatherForecast');
  forecastContainer.innerHTML = '';

  data.forecast.forEach(day => {
    const dayDiv = document.createElement('div');
    dayDiv.classList.add('forecast-day');
    const imgStyle = day.filter ? `style="filter: ${day.filter};"` : '';
    dayDiv.innerHTML = `
      <div class="fw-bold">${day.day}</div>
      <img src="${day.icon}" alt="icon" ${imgStyle}>
      <div>${day.temp}°C</div>
    `;
    forecastContainer.appendChild(dayDiv);
  });
}