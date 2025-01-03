# Getting Started with Create React App

This project was bootstrapped with [Create React App](https://github.com/facebook/create-react-app).

## Available Scripts

# Spotify API Integration

This project demonstrates how to integrate with the Spotify API to authenticate users and fetch their playlists using Python and Flask.

## Prerequisites

Before running the project, make sure you have the following installed on your system:

- **Python**: Version 3.7 or higher

## Setup Instructions

1. **Install Python**:

   - Download Python from the [official website](https://www.python.org/downloads/).
   - Follow the installation instructions for your operating system.
   - Ensure Python is added to your system's PATH.

2. **Install Required Libraries**:
   Run the following command to install the required Python libraries:

   ```bash
   pip install flask requests
   ```

3. **Set Up Spotify App Credentials**:

   - Create a new application in the [Spotify Developer Dashboard](https://developer.spotify.com/dashboard/applications).
   - Note the **Client ID** and **Client Secret**.
   - Set the Redirect URI in the Spotify app settings to `http://localhost:5000/callback`.



## Running the Application

To start the server, run the following command:

```bash
python3 main.py
```

The application will start on `http://localhost:5000`. Open this URL in your browser to access the app.

## Features

- **Login with Spotify**: Authenticate users through Spotify's OAuth 2.0 flow.
- **Fetch User Playlists**: Retrieve and display the user's playlists.
- **Token Refresh**: Automatically refreshes expired tokens using the Spotify API.

## Notes

- Make sure your system clock is accurate; token expiration calculations depend on it.
- Use `https` for the redirect URI in a production environment for better security.

## Troubleshooting

- If you encounter issues with Python or Flask, verify your Python installation with:
  ```bash
  python3 --version
  ```
- For dependency issues, try reinstalling the required libraries:
  ```bash
  pip install --force-reinstall flask requests
  ```

## License

This project is licensed under the MIT License.

