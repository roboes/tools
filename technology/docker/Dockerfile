## Dockerfile Strava Club Scraper
# Last update: 2026-09-13


# Base image
FROM python:latest


# Set working directory
WORKDIR /app


# Install cron and Google Chrome using a repository-scoped signing key
RUN apt-get update \
	&& apt-get install -y --no-install-recommends ca-certificates cron curl gnupg \
	&& install -d -m 0755 /etc/apt/keyrings \
	&& curl -fsSL https://dl.google.com/linux/linux_signing_key.pub \
		| gpg --dearmor -o /etc/apt/keyrings/google-chrome.gpg \
	&& echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/google-chrome.gpg] https://dl.google.com/linux/chrome/deb/ stable main" \
		> /etc/apt/sources.list.d/google-chrome.list \
	&& apt-get update \
	&& apt-get install -y --no-install-recommends google-chrome-stable \
	&& rm -rf /var/lib/apt/lists/*


# Install Python dependencies
RUN python -m pip install --no-cache-dir --upgrade pip \
	&& python -m pip install --no-cache-dir python-dateutil geopy google-api-python-client google-auth lxml numpy pandas selenium webdriver-manager


# Copy all files to the container
COPY . /app


# Create and install the schedule
RUN for t in "00 10" "00 17" "30 21"; do \
		echo "$t * * * cd /app && /usr/local/bin/python app.py >> /proc/1/fd/1 2>> /proc/1/fd/2"; \
	done > /etc/cron.d/app \
	&& chmod 0644 /etc/cron.d/app \
	&& crontab /etc/cron.d/app


# Command to execute cron job on container startup
CMD ["cron", "-f"]
