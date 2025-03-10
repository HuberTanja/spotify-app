class Card {
  constructor({
    artist,
    song,
    imageUrl,
    onDismiss,
    onLike,
    onDislike
  }) {
    this.artist = artist;
    this.song = song;
    this.imageUrl = imageUrl;
    this.onDismiss = onDismiss;
    this.onLike = onLike;
    this.onDislike = onDislike;
    this.#init();
  }

  // private properties
  #startPoint;
  #offsetX;
  #offsetY;

  #isTouchDevice = () => {
      return (('ontouchstart' in window) ||
          (navigator.maxTouchPoints > 0) ||
          (navigator.msMaxTouchPoints > 0));
  }

  #init = () => {
      const card = document.createElement('div');
      card.classList.add('card');
      const albumCover = document.createElement('div');
      albumCover.classList.add('album-cover');
      const img = document.createElement('img');
      img.src = this.imageUrl;
      albumCover.append(img);

      // Hinzufügen des Songtitels
      const songTitle = document.createElement('div');
      songTitle.classList.add('song-title');
      albumCover.append(songTitle);

      // Hinzufügen des Künstlernamens
      const artistName = document.createElement('div');
      artistName.classList.add('artist-name');
      artistName.textContent = this.artist;
      albumCover.append(artistName);

      const buttonContainer = document.createElement('div');
        buttonContainer.classList.add('button-container');

        const likeButton = document.createElement('button');
        likeButton.classList.add('like-button');
        const likeImg = document.createElement('img');
        likeImg.src = '../design/Icons/HeartGreen.png';
        likeImg.alt = 'Like';
        likeButton.appendChild(likeImg);
        likeButton.addEventListener('click', () => this.swipe(1));

        const dislikeButton = document.createElement('button');
        dislikeButton.classList.add('dislike-button');
        const dislikeImg = document.createElement('img');
        dislikeImg.src = '../design/Icons/HeartRed.png';
        dislikeImg.alt = 'Dislike';
        dislikeButton.appendChild(dislikeImg);
        dislikeButton.addEventListener('click', () => this.swipe(-1));

        buttonContainer.appendChild(dislikeButton);
        buttonContainer.appendChild(likeButton);

card.appendChild(buttonContainer);
      card.appendChild(buttonContainer);

      const createScrollingText = (text) => {
          const scrollContainer = document.createElement('div');
          scrollContainer.style.position = 'relative';
          scrollContainer.style.width = '80%';
          scrollContainer.style.marginLeft = '2vw';
          scrollContainer.style.marginRight = '2vw';
          scrollContainer.style.overflow = 'hidden';
          scrollContainer.style.whiteSpace = 'nowrap';

          const textSpan = document.createElement('span');
          textSpan.textContent = text;
          textSpan.style.display = 'inline-block';
          textSpan.style.animation = 'none';

          scrollContainer.appendChild(textSpan);
          songTitle.appendChild(scrollContainer);

          setTimeout(() => {
              if (text.length > 5) {
                  const duration = textSpan.offsetWidth / 30;
                  textSpan.style.animation = `scrollText ${duration}s linear infinite`;

                  const style = document.createElement('style');
                  style.textContent = `
                      @keyframes scrollText {
                          0% { transform: translateX(0); }
                          100% { transform: translateX(-100%); }
                      }
                  `;
                  document.head.appendChild(style);
              } else {
                  scrollContainer.style.textAlign = 'center';
              }
          }, 0);
      };

      createScrollingText(this.song);

      const musicWave = document.createElement('div');
      musicWave.classList.add('music');
      for (let i = 0; i < 10; i++) {
          const bar = document.createElement('div');
          bar.classList.add('bar');
          musicWave.appendChild(bar);
      }
      albumCover.append(musicWave);

      card.append(albumCover);
      this.element = card;
      this.albumCover = albumCover;
      if (this.#isTouchDevice()) {
          this.#listenToTouchEvents();
      } else {
          this.#listenToMouseEvents();
      }
  }

  swipe(direction) {
    // Reset any ongoing transitions or transformations
    this.albumCover.style.transition = '';
    this.albumCover.style.transform = '';

    // Trigger the dismiss animation on the next frame
    requestAnimationFrame(() => {
      this.#dismiss(direction);
    });
  }

  #listenToTouchEvents = () => {
      this.albumCover.addEventListener('touchstart', (e) => {
          const touch = e.changedTouches[0];
          if (!touch) return;
          const { clientX, clientY } = touch;
          this.#startPoint = { x: clientX, y: clientY }
          document.addEventListener('touchmove', this.#handleTouchMove);
          this.albumCover.style.transition = 'transform 0s';
      });

      document.addEventListener('touchend', this.#handleTouchEnd);
      document.addEventListener('cancel', this.#handleTouchEnd);
  }

  #listenToMouseEvents = () => {
      this.albumCover.addEventListener('mousedown', (e) => {
          const { clientX, clientY } = e;
          this.#startPoint = { x: clientX, y: clientY }
          document.addEventListener('mousemove', this.#handleMouseMove);
          this.albumCover.style.transition = 'transform 0s';
      });

      document.addEventListener('mouseup', this.#handleMoveUp);

      this.albumCover.addEventListener('dragstart', (e) => {
          e.preventDefault();
      });
  }

  #handleMove = (x, y) => {
      this.#offsetX = x - this.#startPoint.x;
      this.#offsetY = y - this.#startPoint.y;
      const rotate = this.#offsetX * 0.1;
      this.albumCover.style.transform = `translate(${this.#offsetX}px, ${this.#offsetY}px) rotate(${rotate}deg)`;
      if (Math.abs(this.#offsetX) > this.element.clientWidth * 0.7) {
          this.#dismiss(this.#offsetX > 0 ? 1 : -1);
      }
  }

  #handleMouseMove = (e) => {
      e.preventDefault();
      if (!this.#startPoint) return;
      const { clientX, clientY } = e;
      this.#handleMove(clientX, clientY);
  }

  #handleMoveUp = () => {
      this.#startPoint = null;
      document.removeEventListener('mousemove', this.#handleMouseMove);
      this.albumCover.style.transform = '';
  }

  #handleTouchMove = (e) => {
      if (!this.#startPoint) return;
      const touch = e.changedTouches[0];
      if (!touch) return;
      const { clientX, clientY } = touch;
      this.#handleMove(clientX, clientY);
  }

  #handleTouchEnd = () => {
      this.#startPoint = null;
      document.removeEventListener('touchmove', this.#handleTouchMove);
      this.albumCover.style.transform = '';
  }

  #dismiss = (direction) => {
    this.#startPoint = null;
    document.removeEventListener('mouseup', this.#handleMoveUp);
    document.removeEventListener('mousemove', this.#handleMouseMove);
    document.removeEventListener('touchend', this.#handleTouchEnd);
    document.removeEventListener('touchmove', this.#handleTouchMove);
    
    this.albumCover.style.transition = 'transform 1s';
    this.albumCover.style.transform = `translate(${direction * window.innerWidth}px, ${this.#offsetY || 0}px) rotate(${90 * direction}deg)`;
    this.albumCover.classList.add('dismissing');
    
    setTimeout(() => {
      this.element.remove();
    }, 1000);
    
    if (typeof this.onDismiss === 'function') {
      this.onDismiss();
    }
    if (typeof this.onLike === 'function' && direction === 1) {
      this.onLike();
    }
    if (typeof this.onDislike === 'function' && direction === -1) {
      this.onDislike();
    }
  }
}