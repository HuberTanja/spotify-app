import React, { useState } from 'react';
import axios from 'axios';

function App() {
  const [albums, setAlbums] = useState([]);

  const fetchAlbums = async () => {
    try {
      const response = await axios.get('http://localhost:8080/api/user/saved-albums');
      setAlbums(response.data);
    } catch (error) {
      console.error(error);
    }
  };

  return (
    <div>
      <h1>Meine Spotify-Alben</h1>
      <button onClick={fetchAlbums}>Alben anzeigen</button>
      <ul>
        {albums.map((album, index) => (
          <li key={index}>{album}</li>
        ))}
      </ul>
    </div>
  );
}

export default App;
