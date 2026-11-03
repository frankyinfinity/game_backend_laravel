// timeline.js
// Genera card PIXI per la timeline basate sui dati API

function generateTimelineCards(timelineData, modalUid) {
  const elements = [];
  const details = timelineData.details || [];

  if (!details || details.length === 0) {
    console.log('[Timeline] No timeline details found');
    return elements;
  }

  const cardWidth = 150;
  const cardHeight = 200;
  const cardSpacing = 50;
  const startX = 50;
  const startY = 50;
  const lineHeight = 2; // Red line height
  const lineY = startY + cardHeight / 2; // Line in the middle of cards

  console.log(`[Timeline] Generating ${details.length} timeline cards`);

  // Draw horizontal red line first (behind cards)
  const totalWidth = details.length * cardWidth + (details.length - 1) * cardSpacing;
  const lineElement = {
    uid: `timeline_line_${modalUid}`,
    type: 'rectangle',
    x: startX,
    y: lineY,
    width: totalWidth,
    height: lineHeight,
    color: 0xFF0000, // Red
    attributes: { renderable: true, z_index: 20015 }
  };
  elements.push(lineElement);

  // Generate cards for each timeline detail
  details.forEach((detail, index) => {
    const cardX = startX + index * (cardWidth + cardSpacing);
    const cardY = startY;

    // Card border (rectangle slightly larger, behind background)
    const cardBorder = {
      uid: `timeline_card_border_${modalUid}_${index}`,
      type: 'rectangle',
      x: cardX - 3,
      y: cardY - 3,
      width: cardWidth + 6,
      height: cardHeight + 6,
      color: 0xCCCCCC, // Gray border
      attributes: { renderable: true, z_index: 20020 }
    };
    elements.push(cardBorder);

    // Card background (simple rectangle)
    const cardBackground = {
      uid: `timeline_card_bg_${modalUid}_${index}`,
      type: 'rectangle',
      x: cardX,
      y: cardY,
      width: cardWidth,
      height: cardHeight,
      color: 0xE8E8E8, // Gray background
      attributes: { renderable: true, z_index: 20021 }
    };
    elements.push(cardBackground);

    // Image (using sprite with URL)
    const imageHeight = 120;
    const imageX = cardX + 5;
    const imageY = cardY + 5;
    const imageWidth = cardWidth - 10;

    // Image border (black rectangle slightly larger behind image)
    const imageBorder = {
      uid: `timeline_card_image_border_${modalUid}_${index}`,
      type: 'rectangle',
      x: imageX - 1,
      y: imageY - 1,
      width: imageWidth + 2,
      height: imageHeight + 2,
      color: 0x000000, // Black border
      attributes: { renderable: true, z_index: 20022 }
    };
    elements.push(imageBorder);

    // Image placeholder (gray rectangle)
    const imagePlaceholder = {
      uid: `timeline_card_image_${modalUid}_${index}`,
      type: 'rectangle',
      x: imageX,
      y: imageY,
      width: imageWidth,
      height: imageHeight,
      color: 0xD0D0D0, // Light gray
      attributes: { renderable: true, z_index: 20023 }
    };
    elements.push(imagePlaceholder);

    // Creation date text
    const dateText = {
      uid: `timeline_card_date_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 15,
      text: `Creato: ${detail.creation_date || 'N/A'}`,
      color: 0x333333,
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(dateText);

    // Population life text (Entity vivi) - darker green
    const lifeText = {
      uid: `timeline_card_life_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 35,
      text: `Entity vivi: ${detail.population_life || 0}`,
      color: 0x008800, // Darker green
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(lifeText);

    // Population death text (Entity morti)
    const deathText = {
      uid: `timeline_card_death_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 55,
      text: `Entity morti: ${detail.population_death || 0}`,
      color: 0xAA0000, // Red
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(deathText);
  });

  console.log(`[Timeline] Generated ${elements.length} timeline elements`);
  return elements;
}

module.exports = {
  generateTimelineCards
};
