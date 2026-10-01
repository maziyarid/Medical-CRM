"use client";

import { useState } from "react";
import { images } from "../site-data";

export function BeforeAfter() {
  const [position, setPosition] = useState(52);

  return (
    <div className="comparison" style={{ "--split": `${position}%` } as React.CSSProperties}>
      <img
        src={images.before}
        alt="تصویر نمایشی پیش از درمان"
        className="comparison-image comparison-before"
      />
      <div className="comparison-after-wrap">
        <img
          src={images.after}
          alt="تصویر نمایشی پس از درمان"
          className="comparison-image comparison-after"
        />
      </div>
      <div className="comparison-line" aria-hidden="true">
        <span>↔</span>
      </div>
      <span className="comparison-label comparison-label-before">قبل</span>
      <span className="comparison-label comparison-label-after">بعد</span>
      <label className="sr-only" htmlFor="comparison-range">
        جابه‌جایی مرز مقایسه تصویر قبل و بعد
      </label>
      <input
        id="comparison-range"
        type="range"
        min="5"
        max="95"
        value={position}
        onChange={(event) => setPosition(Number(event.target.value))}
        className="comparison-range"
      />
    </div>
  );
}
