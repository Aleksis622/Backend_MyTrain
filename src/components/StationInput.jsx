import { useEffect, useState } from "react";
import { search as searchStops } from "../api/stops";

/**
 * Text input with station suggestions from GET /stops?search=.
 * Calls onSelect(station) when a suggestion is picked, or onSelect(null) when the text is edited.
 */
function StationInput({ placeholder, value, onChange, onSelect }) {
  const [suggestions, setSuggestions] = useState([]);
  const [open, setOpen] = useState(false);
  const shouldSearch = open && value.trim().length >= 2;

  // Wait until the user stops typing for 250 ms before asking the backend.
  useEffect(() => {
    if (!shouldSearch) return;

    const timer = setTimeout(() => {
      searchStops(value.trim())
        .then((res) => setSuggestions(res.data))
        .catch(() => setSuggestions([]));
    }, 250);

    return () => clearTimeout(timer);
  }, [value, shouldSearch]);

  const handleChange = (e) => {
    onChange(e.target.value);
    onSelect(null);
    setOpen(true);
  };

  const pick = (station) => {
    onChange(station.stop_name);
    onSelect(station);
    setOpen(false);
  };

  return (
    <div className="station-input">
      <input
        type="text"
        placeholder={placeholder}
        value={value}
        onChange={handleChange}
        onBlur={() => setOpen(false)}
        autoComplete="off"
      />

      {shouldSearch && suggestions.length > 0 && (
        <ul className="station-suggestions">
          {suggestions.map((station) => (
            // onMouseDown runs before the input's onBlur closes the list
            <li key={station.stop_id} onMouseDown={() => pick(station)}>
              {station.stop_name}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export default StationInput;
